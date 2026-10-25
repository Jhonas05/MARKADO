<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\SchoolStudentRecord;
use App\Models\StudentIdentityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentSelfRegistrationActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.institutional_email_domains', ['students.example.edu']);
        config()->set('identity.student_self_registration_enabled', true);
        config()->set('identity.automatic_student_activation_enabled', true);
    }

    public function test_pending_student_is_activated_and_linked_only_after_every_gate_passes(): void
    {
        $this->allowActivationSecurityGate();
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);

        $result = app(StudentActivationService::class)->activate(
            $student,
            $record->student_number,
            (string) $student->email,
        );

        $this->assertTrue($result->successful);
        $this->assertTrue($result->changed);
        $this->assertSame('student_activated', $result->reason);
        $this->assertSame(AccountStatus::Active, $student->fresh()->account_status);
        $this->assertSame($student->id, $record->fresh()->user_id);
        $this->assertNotNull($record->fresh()->linked_at);
        $this->assertSame(AccountRole::Student, $student->fresh()->role);
        $this->assertDatabaseHas('student_identity_events', [
            'user_id' => $student->id,
            'outcome' => 'student_activated_automatically',
        ]);
    }

    public function test_activation_is_default_deny_until_the_operational_security_gate_is_approved(): void
    {
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);

        $result = app(StudentActivationService::class)->activate(
            $student,
            $record->student_number,
            (string) $student->email,
        );

        $this->assertFalse($result->successful);
        $this->assertSame('activation_security_policy_unavailable', $result->reason);
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
        $this->assertNull($record->fresh()->user_id);
    }

    public function test_feature_flags_are_trusted_server_side_activation_boundaries(): void
    {
        $this->allowActivationSecurityGate();
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);

        foreach (['student_self_registration_enabled', 'automatic_student_activation_enabled'] as $flag) {
            config()->set("identity.{$flag}", false);
            $result = app(StudentActivationService::class)->activate(
                $student,
                $record->student_number,
                (string) $student->email,
            );
            $this->assertFalse($result->successful);
            $this->assertSame('student_activation_disabled', $result->reason);
            config()->set("identity.{$flag}", true);
        }

        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
        $this->assertNull($record->fresh()->user_id);
    }

    public function test_disabled_disputed_and_non_student_accounts_cannot_auto_activate(): void
    {
        $this->allowActivationSecurityGate();

        $disabled = $this->pendingStudent(AccountStatus::Disabled);
        $disabledRecord = $this->unlinkedRosterRecord($disabled);
        $this->assertSame(
            'account_disabled',
            app(StudentActivationService::class)->activate(
                $disabled,
                $disabledRecord->student_number,
                (string) $disabled->email,
            )->reason,
        );

        $disputed = $this->pendingStudent();
        $disputedRecord = $this->unlinkedRosterRecord(
            $disputed,
            status: StudentVerificationStatus::Disputed,
        );
        $this->assertSame(
            'trusted_roster_identity_disputed',
            app(StudentActivationService::class)->activate(
                $disputed,
                $disputedRecord->student_number,
                (string) $disputed->email,
            )->reason,
        );

        $instructor = User::factory()->create([
            'email' => 'professor@students.example.edu',
            'role' => AccountRole::Instructor,
            'account_status' => AccountStatus::Pending,
        ]);
        $instructorRecord = $this->unlinkedRosterRecord($instructor);
        $this->assertSame(
            'student_account_required',
            app(StudentActivationService::class)->activate(
                $instructor,
                $instructorRecord->student_number,
                (string) $instructor->email,
            )->reason,
        );

        $this->assertSame(AccountStatus::Disabled, $disabled->fresh()->account_status);
        $this->assertSame(AccountStatus::Pending, $disputed->fresh()->account_status);
        $this->assertSame(AccountRole::Instructor, $instructor->fresh()->role);
    }

    public function test_mailbox_roster_and_same_record_evidence_fail_closed(): void
    {
        $this->allowActivationSecurityGate();
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);
        $service = app(StudentActivationService::class);

        $student->forceFill(['email_verified_at' => null])->save();
        $this->assertSame(
            'institutional_mailbox_unverified',
            $service->activate($student->fresh(), $record->student_number, (string) $student->email)->reason,
        );

        $student->forceFill(['email_verified_at' => now()])->save();
        $this->assertSame(
            'trusted_roster_identity_unavailable',
            $service->activate($student->fresh(), '99999999', (string) $student->email)->reason,
        );
        $this->assertSame(
            'institutional_identity_mismatch',
            $service->activate($student->fresh(), $record->student_number, 'other@students.example.edu')->reason,
        );

        $record->update(['is_active' => false]);
        $this->assertSame(
            'trusted_roster_identity_inactive',
            $service->activate($student->fresh(), $record->student_number, (string) $student->email)->reason,
        );

        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
    }

    public function test_existing_account_binding_cannot_be_claimed_by_another_student(): void
    {
        $this->allowActivationSecurityGate();
        $owner = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($owner);
        $record->user()->associate($owner);
        $record->forceFill(['linked_at' => now()])->save();

        $other = $this->pendingStudent(email: 'other@students.example.edu');
        $result = app(StudentActivationService::class)->activate(
            $other,
            $record->student_number,
            (string) $other->email,
        );

        $this->assertFalse($result->successful);
        $this->assertContains($result->reason, [
            'identity_owned_by_another_account',
            'institutional_identity_mismatch',
        ]);
        $this->assertSame($owner->id, $record->fresh()->user_id);
        $this->assertSame(AccountStatus::Pending, $other->fresh()->account_status);
    }

    public function test_repeated_activation_is_idempotent_and_does_not_duplicate_activation_event(): void
    {
        $this->allowActivationSecurityGate();
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);
        $service = app(StudentActivationService::class);

        $first = $service->activate($student, $record->student_number, (string) $student->email);
        $second = $service->activate($student->fresh(), $record->student_number, (string) $student->email);

        $this->assertTrue($first->successful);
        $this->assertTrue($first->changed);
        $this->assertTrue($second->successful);
        $this->assertFalse($second->changed);
        $this->assertSame('student_already_active', $second->reason);
        $this->assertSame(1, StudentIdentityEvent::query()
            ->where('user_id', $student->id)
            ->where('outcome', 'student_activated_automatically')
            ->count());
    }

    public function test_unlinked_roster_rows_are_supported_without_exposing_public_mutations(): void
    {
        $student = $this->pendingStudent();
        $record = $this->unlinkedRosterRecord($student);

        $this->assertNull($record->user_id);
        $this->assertTrue(Schema::hasColumns('school_student_records', ['user_id', 'linked_at']));

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('registration.available', false)
                ->where('registration.selfRegistrationApproved', true)
                ->where('registration.automaticStudentActivationApproved', true)
                ->has('registration.requirements', 4)
                ->has('registration.verificationStates', 4)
                ->where(
                    'registration.resendAcknowledgement',
                    'If the account is eligible, verification instructions will be sent.',
                ));

        foreach (['register', 'forgot-password', 'reset-password'] as $uri) {
            $this->post($uri)->assertStatus(405);
        }

        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => preg_match('/register|verify|activate|roster/', $route->uri()) === 1
            && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []));
        $this->assertFalse(config('assessment.routes_enabled'));
        $this->assertFalse(config('assessment.expiration_enabled'));
        $this->assertFalse(Schema::hasColumn('student_identity_events', 'email'));
        $this->assertFalse(Schema::hasColumn('student_identity_events', 'student_number'));
    }

    private function allowActivationSecurityGate(): void
    {
        $this->app->instance(StudentActivationSecurityGate::class, new class implements StudentActivationSecurityGate
        {
            public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision
            {
                return StudentIdentityDecision::matched();
            }
        });
    }

    private function pendingStudent(
        AccountStatus $status = AccountStatus::Pending,
        ?string $email = null,
    ): User {
        return User::factory()->create([
            'email' => $email ?? 'student-'.fake()->unique()->numerify('########').'@students.example.edu',
            'role' => AccountRole::Student,
            'account_status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    private function unlinkedRosterRecord(
        User $student,
        StudentVerificationStatus $status = StudentVerificationStatus::Verified,
    ): SchoolStudentRecord {
        $verifier = User::factory()->create(['role' => AccountRole::Instructor]);
        $record = new SchoolStudentRecord([
            'student_number' => fake()->unique()->numerify('00######'),
            'institutional_email' => $student->email,
            'verification_status' => $status,
            'is_active' => true,
            'verified_at' => now(),
        ]);
        $record->verifiedBy()->associate($verifier);
        $record->save();

        return $record;
    }
}
