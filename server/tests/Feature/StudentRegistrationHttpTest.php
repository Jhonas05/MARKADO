<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Contracts\InstitutionalVerificationClock;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Identity\Support\LocalRegistrationEnvironment;
use App\Models\InstitutionalEmailVerificationChallenge;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\StudentIdentityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\Support\MutableInstitutionalVerificationClock;
use Tests\TestCase;

class StudentRegistrationHttpTest extends TestCase
{
    use BuildsAssessmentFixtures;
    use RefreshDatabase;

    private RegistrationHttpMailCapture $mail;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'identity.presentation_registration_enabled' => true,
            'identity.student_self_registration_enabled' => true,
            'identity.automatic_student_activation_enabled' => true,
            'identity.institutional_email_verification_enabled' => true,
            'identity.institutional_email_verification_delivery_enabled' => true,
            'identity.institutional_email_verification_hourly_limit' => 5,
            'identity.institutional_email_domains' => ['students.example.edu'],
        ]);
        Route::middleware(['web', 'guest', 'local-registration'])->group(function (): void {
            Route::post('/register', [StudentRegistrationController::class, 'store'])->middleware('throttle:student-registration')->name('registration.store');
            Route::get('/register/verification', [StudentRegistrationController::class, 'show'])->name('registration.verification');
            Route::post('/register/verification', [StudentRegistrationController::class, 'verify'])->middleware('throttle:student-verification')->name('registration.verify');
            Route::post('/register/resend', [StudentRegistrationController::class, 'resend'])->middleware('throttle:student-verification')->name('registration.resend');
        });
        Route::getRoutes()->refreshNameLookups();
        $this->mail = new RegistrationHttpMailCapture;
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, $this->mail);
    }

    public function test_registration_mailbox_activation_and_sign_in_use_real_http_routes(): void
    {
        $record = $this->roster();
        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('registration.available', true));
        $this->post('/register', $this->payload() + ['role' => 'academic_administrator', 'account_status' => 'active', 'name' => 'Untrusted'])
            ->assertRedirect('/register/verification')->assertSessionHasNoErrors();
        $student = User::query()->sole();
        $this->assertSame(AccountRole::Student, $student->role);
        $this->assertSame(AccountStatus::Pending, $student->account_status);
        $this->assertSame('Student', $student->name);
        $this->assertTrue(Hash::check($this->payload()['password'], $student->password));
        $this->assertNull($student->email_verified_at);
        $this->assertSame($student->id, $record->fresh()->user_id);
        $this->assertNull($record->fresh()->linked_at);
        $this->assertSame(StudentVerificationStatus::Verified, $record->fresh()->verification_status);
        $token = $this->mail->messages[0]->token;
        $this->get('/register/verification')->assertOk()->assertDontSee($token);
        $this->assertStringNotContainsString($token, json_encode(InstitutionalEmailVerificationChallenge::query()->sole()->getAttributes()));
        $this->get('/student/dashboard')->assertRedirect('/login');
        $this->post('/register/verification', ['verification_token' => $token])->assertRedirect('/login');
        $this->assertSame(AccountStatus::Active, $student->fresh()->account_status);
        $this->assertNotNull($student->fresh()->email_verified_at);
        $this->assertNotNull($record->fresh()->linked_at);
        $this->assertSame(1, StudentIdentityEvent::query()->where('outcome', 'student_activated_automatically')->count());
        $this->post('/register/verification', ['verification_token' => $token])->assertSessionHasErrors('verification_token');
        $this->post('/login', ['email' => $student->email, 'password' => $this->payload()['password']])->assertRedirect('/student/dashboard');
        $this->get('/student/dashboard')->assertOk()->assertDontSee($student->email)
            ->assertInertia(fn (Assert $page) => $page->missing('auth.user.email')->missing('auth.user.student_number'));
    }

    public function test_duplicates_never_reassign_identity_reset_password_or_create_pending_retries(): void
    {
        $record = $this->roster();
        $this->post('/register', $this->payload())->assertRedirect('/register/verification');
        $student = User::query()->sole();
        $hash = $student->password;
        foreach ([$this->payload(), array_replace($this->payload(), ['email' => 'other@students.example.edu']),
            array_replace($this->payload(), ['student_number' => 'UNKNOWN']),
            array_replace($this->payload(), ['email' => 'new@STUDENTS.EXAMPLE.EDU', 'password' => 'Different-Password-9400', 'password_confirmation' => 'Different-Password-9400'])] as $payload) {
            $this->post('/register', $payload)->assertRedirect('/register/verification');
        }
        $this->assertSame(1, User::query()->count());
        $this->assertSame($hash, $student->fresh()->password);
        $this->assertSame($student->id, $record->fresh()->user_id);
        $this->assertCount(1, $this->mail->messages);
    }

    #[DataProvider('invalidPayloads')]
    public function test_field_validation_rejects_bad_claims(array $changes, array $errorFields): void
    {
        $this->roster();
        $this->post('/register', array_replace($this->payload(), $changes))->assertSessionHasErrors($errorFields);
        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing' => [['student_number' => '', 'email' => '', 'password' => '', 'password_confirmation' => ''], ['student_number', 'email', 'password', 'password_confirmation']],
            'number whitespace' => [['student_number' => ' 00009401'], ['student_number']],
            'invalid email' => [['email' => 'not-mail'], ['email']],
            'array input' => [['email' => ['invalid'], 'student_number' => ['invalid']], ['email', 'student_number']],
            'personal domain' => [['email' => 'student@gmail.com'], ['email']],
            'lookalike domain' => [['email' => 'student@students.example.edu.attacker.test'], ['email']],
            'weak password' => [['password' => 'weak', 'password_confirmation' => 'weak'], ['password']],
            'mismatch' => [['password_confirmation' => 'Mismatch-Password-9400'], ['password']],
        ];
    }

    public function test_unknown_inactive_disputed_wrong_email_and_owned_records_share_generic_outcome(): void
    {
        $record = $this->roster();
        foreach ([['is_active' => false], ['verification_status' => StudentVerificationStatus::Disputed],
            ['verification_status' => StudentVerificationStatus::Unverified], ['verified_at' => null],
            ['institutional_email' => 'other@students.example.edu']] as $changes) {
            $original = $record->getAttributes();
            $record->forceFill($changes)->save();
            $this->post('/register', $this->payload())->assertRedirect('/register/verification')->assertSessionHasNoErrors();
            $this->assertDatabaseCount('users', 0);
            $record->setRawAttributes($original)->save();
            RateLimiter::clear('registration-claim:'.hash_hmac('sha256', '00009401|new@students.example.edu', (string) config('app.key')));
        }
        $this->assertCount(0, $this->mail->messages);
    }

    public function test_existing_email_and_linked_disabled_account_cannot_be_taken_over(): void
    {
        $record = $this->roster();
        $owner = User::factory()->create(['email' => 'NEW@students.example.edu', 'account_status' => AccountStatus::Disabled]);
        $hash = $owner->password;
        $this->post('/register', $this->payload())->assertRedirect('/register/verification');
        $this->assertDatabaseCount('users', 1);
        $this->assertNull($record->fresh()->user_id);
        $record->user()->associate($owner);
        $record->save();
        $this->post('/register', $this->payload())->assertRedirect('/register/verification');
        $this->assertSame($owner->id, $record->fresh()->user_id);
        $this->assertSame($hash, $owner->fresh()->password);
        $this->assertSame(AccountStatus::Disabled, $owner->fresh()->account_status);
    }

    public function test_case_ambiguous_trusted_mailboxes_fail_closed_without_a_new_account(): void
    {
        $this->roster();
        SchoolStudentRecord::query()->create(['student_number' => '00009402',
            'institutional_email' => 'new@STUDENTS.EXAMPLE.EDU', 'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true, 'verified_at' => now()]);
        $this->post('/register', $this->payload())->assertRedirect('/register/verification')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 0);
        $this->assertCount(0, $this->mail->messages);
    }

    public function test_pending_login_resumes_verification_but_never_grants_workspace(): void
    {
        $this->roster();
        $this->post('/register', $this->payload());
        $this->post('/login', ['email' => $this->payload()['email'], 'password' => $this->payload()['password']])
            ->assertRedirect('/register/verification')->assertSessionHas('registration_user_id');
        $this->assertGuest();
        $this->get('/student/dashboard')->assertRedirect('/login');
    }

    public function test_expired_replayed_and_foreign_tokens_fail_and_are_never_flashed(): void
    {
        $this->roster();
        $this->post('/register', $this->payload());
        $token = $this->mail->messages[0]->token;
        $this->app->instance(InstitutionalVerificationClock::class,
            new MutableInstitutionalVerificationClock(InstitutionalEmailVerificationChallenge::query()->sole()->expires_at));
        $this->post('/register/verification', ['verification_token' => $token])
            ->assertSessionHasErrors('verification_token')->assertSessionMissing('_old_input.verification_token');
        $this->assertSame(AccountStatus::Pending, User::query()->sole()->account_status);
        $this->withSession(['registration_user_id' => 999999])->post('/register/verification', ['verification_token' => $token])->assertSessionHasErrors('verification_token');
    }

    public function test_resend_cooldown_registration_rate_limit_and_csrf_are_enforced(): void
    {
        $this->roster();
        $this->post('/register', $this->payload());
        $this->post('/register/resend')->assertSessionHas('success');
        $this->assertCount(1, $this->mail->messages);
        for ($i = 0; $i < 4; $i++) {
            $this->post('/register', $this->payload())->assertRedirect();
        }
        $this->post('/register', $this->payload())->assertStatus(429);
        $this->app['env'] = 'local';
        $this->post('/register', $this->payload())->assertStatus(419);
        $this->app['env'] = 'testing';
    }

    public function test_local_scope_fails_closed_in_production_and_protected_schemas(): void
    {
        $this->assertTrue(LocalRegistrationEnvironment::enabled());
        $this->app['env'] = 'production';
        $this->assertFalse(LocalRegistrationEnvironment::enabled());
        $this->app['env'] = 'local';
        $originalDatabase = config('database.default');
        $originalSchema = config('database.connections.pgsql.search_path');
        config(['database.default' => 'pgsql', 'database.connections.pgsql.search_path' => 'public']);
        $this->assertFalse(LocalRegistrationEnvironment::enabled());
        config(['database.default' => $originalDatabase, 'database.connections.pgsql.search_path' => $originalSchema]);
        $this->app['env'] = 'testing';
    }

    #[DataProvider('assignmentOrders')]
    public function test_quiz_assignment_and_registration_work_in_both_orders(string $order): void
    {
        config(['assessment.routes_enabled' => true]);
        [$owner, $fixtureStudent, $quiz] = $this->assessmentFixture(
            questionSeconds: null, randomize: false,
            opensAt: now()->subHour()->toDateTimeImmutable(), closesAt: now()->addHour()->toDateTimeImmutable(),
        );
        $fixtureStudent->forceFill(['email' => 'fixture@students.example.edu'])->save();
        $record = $fixtureStudent->schoolStudentRecord;
        $record->forceFill(['user_id' => null, 'linked_at' => null, 'student_number' => $this->payload()['student_number'],
            'institutional_email' => $this->payload()['email']])->save();
        QuizStudentAssignment::query()->delete();
        if ($order === 'before') {
            $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/student-assignments", ['student_number' => $record->student_number])->assertSessionHasNoErrors();
            $this->delete('/logout');
        }
        $this->post('/register', $this->payload())->assertRedirect('/register/verification');
        $this->post('/register/verification', ['verification_token' => $this->mail->messages[0]->token])->assertRedirect('/login');
        $student = User::query()->where('email', $this->payload()['email'])->sole();
        $this->actingAs($student);
        if ($order === 'after') {
            $this->post("/student/quizzes/{$quiz->id}/attempts")->assertRedirect()->assertSessionHasErrors('assessment');
            $this->assertDatabaseCount('quiz_attempts', 0);
            $this->actingAs($owner)->post("/professor/quizzes/{$quiz->id}/student-assignments", ['student_number' => $record->student_number])->assertSessionHasNoErrors();
            $this->actingAs($student);
        }
        $this->post("/student/quizzes/{$quiz->id}/attempts")->assertRedirect();
        $this->assertDatabaseHas('quiz_attempts', ['student_id' => $student->id, 'quiz_id' => $quiz->id]);
        $this->post("/student/quizzes/{$quiz->id}/attempts")->assertRedirect();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public static function assignmentOrders(): array
    {
        return [['before'], ['after']];
    }

    private function payload(): array
    {
        return ['student_number' => '00009401', 'email' => 'new@students.example.edu',
            'password' => 'Synthetic-Password-9400', 'password_confirmation' => 'Synthetic-Password-9400'];
    }

    private function roster(): SchoolStudentRecord
    {
        return SchoolStudentRecord::query()->create(['student_number' => '00009401', 'institutional_email' => 'new@students.example.edu',
            'verification_status' => StudentVerificationStatus::Verified, 'is_active' => true, 'verified_at' => now()]);
    }
}

final class RegistrationHttpMailCapture implements InstitutionalEmailVerificationDelivery
{
    public array $messages = [];

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $this->messages[] = $message;

        return true;
    }
}
