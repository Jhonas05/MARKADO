<?php

namespace Tests\Feature;

use App\Assessment\Support\DatabaseQuizEligibilityGate;
use App\Assessment\Support\DatabaseSchoolEligibilitySource;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\StudentIdentitySource;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\Quiz;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class StudentIdentityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_institutional_email_and_matching_active_roster_are_required_without_auto_activation(): void
    {
        $student = $this->student(AccountStatus::Pending);
        $record = $this->rosterRecord($student);
        $source = app(StudentIdentitySource::class);

        $pending = $source->matchClaim($student, $record->student_number, (string) $student->email);
        $this->assertFalse($pending->matched);
        $this->assertSame('account_not_active', $pending->reason);
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);

        $student->update(['account_status' => AccountStatus::Active]);
        $matched = $source->matchClaim($student->fresh(), $record->student_number, (string) $student->email);

        $this->assertTrue($matched->matched);
        $this->assertSame('matched', $matched->reason);
    }

    public function test_missing_or_mismatched_roster_identity_denies_by_default(): void
    {
        $student = $this->student();
        $source = app(StudentIdentitySource::class);

        $this->assertSame('trusted_roster_record_missing', $source->evaluate($student)->reason);

        $record = $this->rosterRecord($student);
        $this->assertSame(
            'trusted_roster_record_missing',
            $source->matchClaim($student, '00000000', (string) $student->email)->reason,
        );
        $this->assertSame(
            'claimed_email_mismatch',
            $source->matchClaim($student, $record->student_number, 'other@example.test')->reason,
        );

        $record->update(['institutional_email' => 'roster-owner@example.test']);
        $this->assertSame('roster_email_mismatch', $source->evaluate($student)->reason);
    }

    public function test_unverified_mailbox_inactive_or_unverified_roster_and_restricted_accounts_are_denied(): void
    {
        $student = $this->student();
        $record = $this->rosterRecord($student);
        $source = app(StudentIdentitySource::class);

        $student->forceFill(['email_verified_at' => null])->save();
        $this->assertSame('institutional_email_unverified', $source->evaluate($student->fresh())->reason);

        $student->forceFill(['email_verified_at' => now()])->save();
        $record->update(['verification_status' => StudentVerificationStatus::Unverified]);
        $this->assertSame('trusted_roster_record_unverified', $source->evaluate($student->fresh())->reason);

        $record->update([
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => false,
        ]);
        $this->assertSame('trusted_roster_record_inactive', $source->evaluate($student->fresh())->reason);

        $record->update(['is_active' => true]);
        foreach ([AccountStatus::Pending, AccountStatus::Disabled] as $status) {
            $student->update(['account_status' => $status]);
            $this->assertSame('account_not_active', $source->evaluate($student->fresh())->reason);
        }
    }

    public function test_email_change_invalidates_mailbox_verification_and_identity_match(): void
    {
        $student = $this->student();
        $this->rosterRecord($student);

        $student->update(['email' => 'replacement@example.test']);

        $student->refresh();
        $this->assertNull($student->email_verified_at);
        $this->assertSame(
            'institutional_email_unverified',
            app(StudentIdentitySource::class)->evaluate($student)->reason,
        );
        $this->assertSame(AccountStatus::Active, $student->account_status);
    }

    public function test_database_rejects_duplicate_student_number_email_and_account_claims(): void
    {
        $first = $this->student();
        $second = $this->student();
        $record = $this->rosterRecord($first, '00001234', 'claim@example.test');

        foreach ([
            ['student' => $second, 'student_number' => $record->student_number, 'institutional_email' => 'other@example.test'],
            ['student' => $second, 'student_number' => '00005678', 'institutional_email' => $record->institutional_email],
            ['student' => $first, 'student_number' => '00009999', 'institutional_email' => 'second-claim@example.test'],
        ] as $duplicate) {
            try {
                DB::transaction(fn () => $this->rosterRecord(
                    $duplicate['student'],
                    $duplicate['student_number'],
                    $duplicate['institutional_email'],
                ));
                $this->fail('A duplicate trusted identity must be rejected by the database.');
            } catch (QueryException) {
                $this->assertDatabaseCount('school_student_records', 1);
            }
        }
    }

    public function test_students_can_authorize_only_their_own_identity_record(): void
    {
        $owner = $this->student();
        $otherStudent = $this->student();
        $administrator = User::factory()->create(['role' => AccountRole::AcademicAdministrator]);
        $record = $this->rosterRecord($owner);

        $this->assertTrue($owner->can('view', $record));
        $this->assertFalse($otherStudent->can('view', $record));
        $this->assertFalse($administrator->can('view', $record));
        $this->assertFalse($administrator->can('update', $record));
    }

    public function test_unavailable_identity_source_fails_closed(): void
    {
        $source = new class implements StudentIdentitySource
        {
            public function evaluate(User $student): StudentIdentityDecision
            {
                throw new RuntimeException('Synthetic trusted roster outage');
            }

            public function matchClaim(User $student, string $studentNumber, string $institutionalEmail): StudentIdentityDecision
            {
                throw new RuntimeException('Synthetic trusted roster outage');
            }
        };
        $gate = new DatabaseQuizEligibilityGate(new DatabaseSchoolEligibilitySource($source));

        $decision = $gate->evaluate($this->student(), new Quiz);

        $this->assertFalse($decision->allowed);
        $this->assertSame('trusted_source_unavailable', $decision->reason);
    }

    public function test_identity_audit_and_inertia_projections_exclude_email_and_roster_identifiers(): void
    {
        $student = $this->student();
        $this->rosterRecord($student);
        app(StudentIdentitySource::class)->evaluate($student);

        $this->assertFalse(Schema::hasColumn('student_identity_events', 'email'));
        $this->assertFalse(Schema::hasColumn('student_identity_events', 'student_number'));
        $this->assertFalse(Schema::hasColumn('student_identity_events', 'school_student_record_id'));
        $this->assertDatabaseHas('student_identity_events', [
            'user_id' => $student->id,
            'outcome' => 'matched',
        ]);

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('auth.user.email')
                ->missing('eligibility.institutionalEmail')
                ->where('eligibility.mailboxVerified', true)
                ->where('eligibility.identityMatched', true));
    }

    public function test_registration_recovery_and_privileged_mutations_remain_unavailable(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('registration.available', false)
                ->where('registration.selfRegistrationApproved', true)
                ->where('registration.automaticStudentActivationApproved', true)
                ->has('registration.requirements', 4));

        foreach (['register', 'forgot-password', 'reset-password'] as $uri) {
            $this->post($uri)->assertStatus(405);
        }

        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => preg_match('/roster|identity|provision|regrad|override/', $route->uri()) === 1
            && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []));
        $this->assertFalse(config('assessment.routes_enabled'));
        $this->assertFalse(config('assessment.expiration_enabled'));
    }

    private function student(AccountStatus $status = AccountStatus::Active): User
    {
        return User::factory()->create([
            'role' => AccountRole::Student,
            'account_status' => $status,
        ]);
    }

    private function rosterRecord(
        User $student,
        ?string $studentNumber = null,
        ?string $institutionalEmail = null,
    ): SchoolStudentRecord {
        $verifier = User::factory()->create(['role' => AccountRole::Instructor]);
        $record = new SchoolStudentRecord([
            'student_number' => $studentNumber ?? '00'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'institutional_email' => $institutionalEmail ?? $student->email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => now(),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($verifier);
        $record->save();

        return $record;
    }
}
