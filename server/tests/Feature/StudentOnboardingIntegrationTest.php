<?php

namespace Tests\Feature;

use App\Assessment\Contracts\QuizEligibilityGate;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\InstitutionalVerificationAbuseGate;
use App\Identity\Contracts\InstitutionalVerificationClock;
use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\Quiz;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\MutableInstitutionalVerificationClock;
use Tests\TestCase;

class StudentOnboardingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingVerificationDelivery $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.institutional_email_domains', ['students.example.edu']);
        config()->set('identity.institutional_email_verification_enabled', true);
        config()->set('identity.institutional_email_verification_delivery_enabled', true);
        config()->set('identity.student_self_registration_enabled', true);
        config()->set('identity.automatic_student_activation_enabled', true);

        $this->delivery = new OnboardingVerificationDelivery;
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, $this->delivery);
        $this->app->instance(InstitutionalVerificationClock::class, new MutableInstitutionalVerificationClock(
            CarbonImmutable::parse('2026-10-25 08:00:00', 'UTC'),
        ));
        $this->app->instance(InstitutionalVerificationAbuseGate::class, new AllowOnboardingSecurityGate);
        $this->app->instance(StudentActivationSecurityGate::class, new AllowOnboardingSecurityGate);
    }

    public function test_existing_services_compose_into_a_bounded_student_only_onboarding_flow(): void
    {
        $student = $this->pendingStudent('onboarding@students.example.edu');
        [$record, $verifier] = $this->trustedRosterRecord($student->email);
        $verification = app(InstitutionalEmailVerificationService::class);

        $issued = $verification->issue($student, 'synthetic-school-network');
        $this->assertTrue($issued->successful);
        $this->assertSame($student->id, $this->delivery->message->studentId);
        $this->assertSame($student->email, $this->delivery->message->recipient);

        $verified = $verification->consume($student->fresh(), $this->delivery->message->token);
        $this->assertTrue($verified->successful);
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);

        $activated = app(StudentActivationService::class)->activate(
            $student->fresh(),
            $record->student_number,
            $record->institutional_email,
        );

        $this->assertTrue($activated->successful);
        $this->assertTrue($activated->changed);
        $this->assertSame(AccountRole::Student, $student->fresh()->role);
        $this->assertSame(AccountStatus::Active, $student->fresh()->account_status);
        $this->assertSame($student->id, $record->fresh()->user_id);

        $quiz = $this->quiz($verifier);
        $notAssigned = app(QuizEligibilityGate::class)->evaluate($student->fresh(), $quiz);
        $this->assertFalse($notAssigned->allowed);
        $this->assertSame('not_assigned_to_quiz', $notAssigned->reason);

        $assignment = new QuizStudentAssignment(['is_active' => true]);
        $assignment->quiz()->associate($quiz);
        $assignment->studentRecord()->associate($record->fresh());
        $assignment->assignedBy()->associate($verifier);
        $assignment->save();

        $this->assertTrue(app(QuizEligibilityGate::class)
            ->evaluate($student->fresh(), $quiz->fresh())->allowed);
        $this->assertDatabaseHas('student_identity_events', [
            'user_id' => $student->id,
            'outcome' => 'student_activated_automatically',
        ]);
    }

    public function test_wrong_number_email_and_inactive_or_disputed_roster_fail_closed(): void
    {
        $student = $this->pendingStudent('failure-matrix@students.example.edu', verified: true);
        [$record] = $this->trustedRosterRecord($student->email);
        $activation = app(StudentActivationService::class);

        $this->assertSame('trusted_roster_identity_unavailable', $activation->activate(
            $student,
            'WRONG-NUMBER',
            $student->email,
        )->reason);
        $this->assertSame('institutional_identity_mismatch', $activation->activate(
            $student,
            $record->student_number,
            'other@students.example.edu',
        )->reason);

        $record->update(['is_active' => false]);
        $this->assertSame('trusted_roster_identity_inactive', $activation->activate(
            $student,
            $record->student_number,
            $student->email,
        )->reason);

        $record->update([
            'is_active' => true,
            'verification_status' => StudentVerificationStatus::Disputed,
        ]);
        $this->assertSame('trusted_roster_identity_disputed', $activation->activate(
            $student,
            $record->student_number,
            $student->email,
        )->reason);
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
        $this->assertNull($record->fresh()->user_id);
    }

    public function test_duplicate_identity_and_disabled_account_cannot_activate(): void
    {
        $owner = User::factory()->create([
            'email' => 'identity-owner@students.example.edu',
            'email_verified_at' => now(),
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
        ]);
        $claimant = $this->pendingStudent('identity-owner@students.example.edu', verified: true);
        [$record] = $this->trustedRosterRecord($owner->email, $owner);

        $duplicate = app(StudentActivationService::class)->activate(
            $claimant,
            $record->student_number,
            $claimant->email,
        );
        $this->assertFalse($duplicate->successful);
        $this->assertSame('identity_owned_by_another_account', $duplicate->reason);

        $claimant->forceFill(['account_status' => AccountStatus::Disabled])->save();
        $disabled = app(StudentActivationService::class)->activate(
            $claimant->fresh(),
            $record->student_number,
            $claimant->email,
        );
        $this->assertFalse($disabled->successful);
        $this->assertSame('account_disabled', $disabled->reason);
        $this->assertSame($owner->id, $record->fresh()->user_id);
    }

    public function test_production_defaults_and_public_mutation_surface_remain_closed(): void
    {
        $this->assertSame(5, config('identity.institutional_email_verification_hourly_limit'));

        $example = file_get_contents(base_path('.env.example'));
        $this->assertIsString($example);
        foreach ([
            'MARKADO_STUDENT_SELF_REGISTRATION_ENABLED=false',
            'MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED=false',
            'MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED=false',
            'MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED=false',
            'MARKADO_ASSESSMENT_ROUTES_ENABLED=false',
            'MARKADO_ASSESSMENT_EXPIRATION_ENABLED=false',
        ] as $disabledSetting) {
            $this->assertStringContainsString($disabledSetting, $example);
        }

        foreach (['student_self_registration_enabled', 'automatic_student_activation_enabled',
            'institutional_email_verification_enabled', 'institutional_email_verification_delivery_enabled'] as $key) {
            config()->set("identity.{$key}", false);
            $this->assertFalse(config("identity.{$key}"));
        }

        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => preg_match(
            '/register|verify|verification|activate|roster/',
            $route->uri(),
        ) === 1 && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []));
    }

    private function pendingStudent(string $email, bool $verified = false): User
    {
        return User::factory()->create([
            'email' => $email,
            'email_verified_at' => $verified ? now() : null,
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Pending,
        ]);
    }

    /** @return array{SchoolStudentRecord, User} */
    private function trustedRosterRecord(string $email, ?User $owner = null): array
    {
        $verifier = User::factory()->create(['role' => AccountRole::Instructor]);
        $record = new SchoolStudentRecord([
            'student_number' => 'SYN-'.fake()->unique()->numerify('########'),
            'institutional_email' => $email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => now(),
        ]);
        if ($owner !== null) {
            $record->user()->associate($owner);
            $record->linked_at = now();
        }
        $record->verifiedBy()->associate($verifier);
        $record->save();

        return [$record, $verifier];
    }

    private function quiz(User $owner): Quiz
    {
        $quiz = new Quiz([
            'title' => 'Synthetic onboarding separation check',
            'course_code' => 'SYN 101',
            'course_name' => 'Synthetic Testing',
            'instructions' => 'No live assessment is created by this test.',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = 'SYNV8801';
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        return $quiz;
    }
}

final class OnboardingVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public InstitutionalEmailVerificationMessage $message;

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $this->message = $message;

        return true;
    }
}

final class AllowOnboardingSecurityGate implements InstitutionalVerificationAbuseGate, StudentActivationSecurityGate
{
    public function evaluate(User $student, ?string $networkOrRosterContext): StudentIdentityDecision
    {
        return StudentIdentityDecision::matched();
    }
}
