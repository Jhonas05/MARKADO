<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Mail\InstitutionalEmailVerificationMail;
use App\Models\StudentSignupClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\TestCase;

class StudentSignupHttpTest extends TestCase
{
    use BuildsAssessmentFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identity.presentation_registration_enabled' => true,
            'identity.student_self_registration_enabled' => true,
            'identity.student_claim_signup_enabled' => true,
            'identity.automatic_student_activation_enabled' => true,
            'identity.institutional_email_verification_enabled' => true,
            'identity.institutional_email_verification_delivery_enabled' => true,
            'identity.institutional_email_domains' => ['students.example.edu'],
            'assessment.routes_enabled' => true,
        ]);

        Route::middleware('web')->group(base_path('routes/web.php'));
        Route::getRoutes()->refreshNameLookups();
        Mail::fake();
    }

    public function test_new_student_can_activate_without_a_roster_and_cannot_inherit_quiz_access(): void
    {
        [, $otherStudent, $quiz] = $this->assessmentFixture();
        $number = $otherStudent->schoolStudentRecord->student_number;

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('accountPolicies.publicStudentRegistration', true));
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('registration.available', true));
        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('registration.claimSignup', true));

        $this->post('/register', $this->details($number) + ['role' => 'academic_administrator', 'account_status' => 'active'])
            ->assertRedirect('/register/verification');
        $student = User::query()->where('email', 'new@students.example.edu')->sole();
        $this->assertSame(AccountRole::Student, $student->role);
        $this->assertSame(AccountStatus::Pending, $student->account_status);
        $this->assertNull($student->schoolStudentRecord);
        $this->assertNull($student->email_verified_at);
        $this->assertFalse(Hash::check('Synthetic-Signup-9400', $student->password));
        $this->assertSame($number, StudentSignupClaim::query()->findOrFail($student->id)->student_number);
        $this->assertNotSame($number, DB::table('student_signup_claims')->where('user_id', $student->id)->value('student_number'));
        $this->get('/student/dashboard')->assertRedirect('/login');

        $this->post('/register/verification', ['verification_token' => str_repeat('A', 43)])
            ->assertSessionHasErrors('verification_token');
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
        Mail::assertSent(InstitutionalEmailVerificationMail::class, fn ($mail) => $mail->hasTo($student->email));
        $token = Mail::sent(InstitutionalEmailVerificationMail::class)->sole()->verificationToken;
        $this->post('/register/verification', ['verification_token' => $token])->assertRedirect('/login');
        $this->assertSame(AccountStatus::Active, $student->fresh()->account_status);
        $this->assertNotNull($student->fresh()->email_verified_at);
        $this->assertSame($otherStudent->id, $otherStudent->schoolStudentRecord->fresh()->user_id);
        $this->post('/register/verification', ['verification_token' => $token])->assertSessionHasErrors('verification_token');

        $this->post('/login', ['email' => $student->email, 'password' => 'Synthetic-Signup-9400'])
            ->assertRedirect('/student/dashboard');
        $this->get('/student/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('eligibility.mailboxVerified', true)->where('eligibility.identityMatched', false)
            ->where('eligibility.studentNumber', null));
        $this->get('/student/quizzes/join/'.$quiz->access_code)
            ->assertInertia(fn (Assert $page) => $page->where('preflight.state', 'unavailable')->where('preflight.quiz', null));
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/professor/quizzes')->assertForbidden();
    }

    public function test_mailbox_owner_can_finish_in_a_new_session_with_their_own_password_and_number(): void
    {
        $this->post('/register', $this->details('FIRST-CLAIM'))->assertRedirect('/register/verification');
        $student = User::query()->sole();
        $firstHash = $student->password;
        $token = Mail::sent(InstitutionalEmailVerificationMail::class)->sole()->verificationToken;

        $this->flushSession();
        $this->post('/register', [
            ...$this->details('OWNER-CLAIM'),
            'email' => 'NEW@STUDENTS.EXAMPLE.EDU',
            'password' => 'Real-Owner-Password-9000',
            'password_confirmation' => 'Real-Owner-Password-9000',
        ])->assertRedirect('/register/verification');
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($firstHash, $student->fresh()->password);
        Mail::assertSentCount(1);
        $this->post('/register/verification', ['verification_token' => $token])->assertRedirect('/login');
        $this->assertSame('OWNER-CLAIM', StudentSignupClaim::query()->sole()->student_number);
        $this->assertTrue(Hash::check('Real-Owner-Password-9000', $student->fresh()->password));
        $this->assertFalse(Hash::check('Synthetic-Signup-9400', $student->fresh()->password));

        $this->post('/register', $this->details('NEW-CLAIM'))->assertRedirect('/register/verification')
            ->assertSessionMissing('registration_candidate');
        Mail::assertSentCount(1);
        $this->assertSame('OWNER-CLAIM', StudentSignupClaim::query()->sole()->student_number);
    }

    public function test_unverified_number_claim_does_not_reserve_the_number_or_attach_to_the_roster(): void
    {
        $this->post('/register', $this->details('SYN-ONE'))->assertRedirect('/register/verification');
        $this->flushSession();
        $this->post('/register', [...$this->details('SYN-ONE'), 'email' => 'second@students.example.edu'])
            ->assertRedirect('/register/verification');
        $this->assertDatabaseCount('student_signup_claims', 2);
        $this->assertDatabaseCount('school_student_records', 0);
        $mail = Mail::sent(InstitutionalEmailVerificationMail::class, fn ($mail) => $mail->hasTo('second@students.example.edu'))->sole();
        $this->post('/register/verification', ['verification_token' => $mail->verificationToken])->assertRedirect('/login');
        $this->assertDatabaseHas('users', ['email' => 'second@students.example.edu', 'account_status' => 'active']);
        $this->assertDatabaseHas('users', ['email' => 'new@students.example.edu', 'account_status' => 'pending']);
    }

    public function test_existing_staff_mailbox_is_never_reused_by_signup(): void
    {
        $staff = User::factory()->create(['email' => 'new@students.example.edu', 'role' => AccountRole::Instructor]);
        $this->post('/register', $this->details('CLAIM'))->assertRedirect('/register/verification')
            ->assertSessionMissing('registration_candidate');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('student_signup_claims', 0);
        $this->assertSame($staff->password, $staff->fresh()->password);
        Mail::assertNothingSent();
    }

    private function details(string $number): array
    {
        return [
            'student_number' => $number,
            'email' => 'new@students.example.edu',
            'password' => 'Synthetic-Signup-9400',
            'password_confirmation' => 'Synthetic-Signup-9400',
        ];
    }
}
