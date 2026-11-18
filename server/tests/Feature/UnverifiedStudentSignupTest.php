<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Http\Controllers\Auth\UnverifiedStudentRegistrationController;
use App\Identity\Support\UnverifiedStudentSignupEnvironment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UnverifiedStudentSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.unverified_student_signup_enabled', true);
        config()->set('identity.presentation_registration_enabled', false);
        config()->set('identity.public_student_registration_enabled', false);

        Route::middleware(['web', 'guest', 'unverified-student-signup'])
            ->post('/register', [UnverifiedStudentRegistrationController::class, 'store'])
            ->middleware('throttle:student-registration')
            ->name('registration.store');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_student_creates_a_local_login_without_mailbox_verification_or_roster_approval(): void
    {
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('registration.available', true)
            ->where('registration.mode', 'unverified'));

        $this->post('/register', $this->payload() + [
            'role' => 'academic_administrator',
            'account_status' => 'disabled',
            'email_verified_at' => now()->toISOString(),
        ])->assertRedirect('/student/dashboard');

        $student = User::query()->sole();
        $this->assertAuthenticatedAs($student);
        $this->assertSame(AccountRole::Student, $student->role);
        $this->assertSame(AccountStatus::Active, $student->account_status);
        $this->assertNull($student->email_verified_at);
        $this->assertNull($student->schoolStudentRecord);
        $this->assertTrue(Hash::check($this->payload()['password'], $student->password));
        $this->assertDatabaseHas('student_self_reported_claims', [
            'user_id' => $student->id,
            'student_number' => '00009401',
        ]);
        $this->assertDatabaseCount('school_student_records', 0);
        $this->assertDatabaseCount('institutional_email_verification_challenges', 0);

        $this->get('/student/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('eligibility.hasSchoolRecord', false)
            ->where('eligibility.mailboxVerified', false)
            ->where('eligibility.identityMatched', false)
            ->where('eligibility.verificationLabel', 'Self-reported (unverified)')
            ->where('eligibility.studentNumber', '00009401'));

        $this->get('/professor/quizzes')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();

        $this->delete('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => $student->email,
            'password' => $this->payload()['password'],
        ])->assertRedirect('/student/dashboard');
    }

    public function test_existing_email_is_not_taken_over_or_given_a_new_password(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/student/dashboard');
        $student = User::query()->sole();
        $passwordHash = $student->password;

        $this->delete('/logout');
        $this->from('/register')->post('/register', array_replace($this->payload(), [
            'email' => 'NEW@students.example.edu',
            'password' => 'Different-Password-9400',
            'password_confirmation' => 'Different-Password-9400',
        ]))->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('student_self_reported_claims', 1);
        $this->assertSame($passwordHash, $student->fresh()->password);
    }

    public function test_invalid_claim_or_password_is_rejected(): void
    {
        $this->post('/register', array_replace($this->payload(), [
            'student_number' => ' 00009401',
            'email' => 'not-an-email',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]))->assertSessionHasErrors(['student_number', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_unverified_student_numbers_never_create_or_modify_trusted_roster_records(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/student/dashboard');
        $this->delete('/logout');

        // A self-declared number may be duplicated. It is not an authoritative identity binding.
        $this->post('/register', array_replace($this->payload(), [
            'email' => 'another@students.example.edu',
        ]))->assertRedirect('/student/dashboard');

        $this->assertDatabaseCount('student_self_reported_claims', 2);
        $this->assertDatabaseCount('school_student_records', 0);
        $this->assertSame(2, DB::table('student_self_reported_claims')
            ->where('student_number', '00009401')->count());
    }

    public function test_claiming_an_existing_roster_student_number_never_grants_school_identity(): void
    {
        \App\Models\SchoolStudentRecord::query()->create([
            'student_number' => '00009401',
            'institutional_email' => 'verified@students.example.edu',
            'verification_status' => \App\Enums\StudentVerificationStatus::Verified,
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $this->post('/register', $this->payload())->assertRedirect('/student/dashboard');
        $student = User::query()->sole();

        $this->assertFalse(app(\App\Identity\Contracts\StudentIdentitySource::class)->evaluate($student)->matched);
        $this->assertNull($student->schoolStudentRecord);
        $this->assertDatabaseHas('student_self_reported_claims', [
            'user_id' => $student->id,
            'student_number' => '00009401',
        ]);
        $this->assertDatabaseHas('school_student_records', [
            'student_number' => '00009401',
            'user_id' => null,
        ]);
    }

    public function test_mode_is_default_deny_and_cannot_override_institutional_signup(): void
    {
        config()->set('identity.unverified_student_signup_enabled', false);
        $this->assertFalse(UnverifiedStudentSignupEnvironment::enabled());
        $this->get('/register')->assertInertia(fn (Assert $page) => $page
            ->where('registration.available', false));
        $this->post('/register', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('users', 0);

        config()->set('identity.unverified_student_signup_enabled', true);
        config()->set('identity.public_student_registration_enabled', true);
        $this->assertFalse(UnverifiedStudentSignupEnvironment::enabled());
    }

    public function test_public_origin_must_be_approved_and_use_https(): void
    {
        $this->app['env'] = 'production';
        config()->set('app.url', 'https://markado.example.test');
        config()->set('app.debug', false);
        config()->set('database.default', 'pgsql');

        $this->assertTrue(UnverifiedStudentSignupEnvironment::availableFor(
            Request::create('https://markado.example.test/register')
        ));
        $this->assertFalse(UnverifiedStudentSignupEnvironment::availableFor(
            Request::create('http://markado.example.test/register')
        ));
        $this->assertFalse(UnverifiedStudentSignupEnvironment::availableFor(
            Request::create('https://other.example.test/register')
        ));
    }

    private function payload(): array
    {
        return [
            'student_number' => '00009401',
            'email' => 'new@students.example.edu',
            'password' => 'Synthetic-Password-9400',
            'password_confirmation' => 'Synthetic-Password-9400',
        ];
    }
}
