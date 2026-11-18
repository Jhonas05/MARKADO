<?php

namespace Tests\Feature;

use App\Assessment\Contracts\SchoolEligibilitySource;
use App\Enums\AccountRole;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Identity\Contracts\StudentIdentitySource;
use App\Identity\Support\UnverifiedStudentRegistrationService;
use App\Mail\InstitutionalEmailVerificationMail;
use App\Models\Quiz;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalSchoolVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private SchoolStudentRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'identity.unverified_student_signup_enabled' => true,
            'identity.local_academic_verification_enabled' => true,
            'identity.institutional_email_verification_enabled' => true,
            'identity.institutional_email_verification_delivery_enabled' => true,
            'identity.institutional_email_domains' => ['example.test'],
        ]);
        Route::middleware('web')->group(base_path('routes/web.php'));
        Route::getRoutes()->refreshNameLookups();
        Mail::fake();
        $this->record = SchoolStudentRecord::create([
            'student_number' => 'DEMO-41',
            'institutional_email' => 'student@example.test',
            'verification_status' => StudentVerificationStatus::Verified,
            'verified_at' => now(),
            'is_active' => true,
        ]);
        $this->student = app(UnverifiedStudentRegistrationService::class)->register('DEMO-41', 'student@example.test', 'InitialLoginOnly41');
    }

    private function obtainCode(): string
    {
        $this->actingAs($this->student)->post('/student/school-verification/send')->assertRedirect('/student/dashboard');

        return Mail::sent(InstitutionalEmailVerificationMail::class)->sole()->verificationToken;
    }

    private function confirm(string $token)
    {
        return $this->post('/student/school-verification/confirm', [
            'verification_token' => $token, 'password' => 'DistinctSchoolLogin42', 'password_confirmation' => 'DistinctSchoolLogin42',
        ]);
    }

    public function test_proof_links_only_the_existing_record_replaces_credentials_and_grants_no_quiz_assignment(): void
    {
        $code = $this->obtainCode();
        $this->assertFalse(app(StudentIdentitySource::class)->evaluate($this->student)->matched);
        $this->confirm($code)->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame($this->student->id, $this->record->fresh()->user_id);
        $this->assertTrue(app(StudentIdentitySource::class)->evaluate($this->student)->matched);
        $this->assertFalse(Hash::check('InitialLoginOnly41', $this->student->fresh()->password));
        $this->assertDatabaseCount('quiz_student_assignments', 0);
        $this->actingAs($this->student);
        $this->confirm($code)->assertSessionHasErrors('verification_token');
    }

    public function test_only_the_owner_can_assign_the_quiz_after_academic_verification(): void
    {
        $owner = User::factory()->create(['role' => AccountRole::Instructor]);
        $other = User::factory()->create(['role' => AccountRole::Instructor]);
        $quiz = new Quiz(['title' => 'Local authorization rehearsal', 'course_code' => 'HCI', 'course_name' => 'HCI', 'instructions' => 'Select one.']);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = 'LOCALTST';
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();

        $path = "/professor/quizzes/{$quiz->id}/student-assignments";
        $assignment = ['student_number' => $this->record->student_number];
        $this->actingAs($owner)->post($path, $assignment)->assertSessionHasErrors('student_number');
        $code = $this->obtainCode();
        $this->confirm($code)->assertRedirect('/login');

        $eligibility = app(SchoolEligibilitySource::class);
        $this->assertFalse($eligibility->evaluate($this->student->fresh(), $quiz)->allowed);
        $this->actingAs($other)->post($path, $assignment)->assertForbidden();
        $this->actingAs($this->student)->post($path, $assignment)->assertForbidden();
        $this->actingAs($owner)->post($path, $assignment)->assertSessionHas('success', 'Student assigned to this quiz.');
        $this->assertTrue($eligibility->evaluate($this->student->fresh(), $quiz)->allowed);
    }

    public function test_invalid_code_or_revoked_roster_leaves_the_account_and_challenge_unchanged(): void
    {
        $code = $this->obtainCode();
        $this->confirm(str_repeat('x', 43))->assertSessionHasErrors('verification_token');
        $this->record->update(['is_active' => false]);
        $this->confirm($code)->assertSessionHasErrors('verification_token');
        $this->assertNull($this->student->fresh()->email_verified_at);
        $this->assertNull($this->record->fresh()->user_id);
        $this->assertNull(DB::table('institutional_email_verification_challenges')->sole()->consumed_at);
    }

    public function test_different_mailbox_cannot_request_a_code_and_another_account_cannot_redeem_it(): void
    {
        $other = app(UnverifiedStudentRegistrationService::class)->register('DEMO-41', 'other@example.test', 'DifferentLogin42');
        $this->actingAs($other)->post('/student/school-verification/send')->assertRedirect();
        Mail::assertNothingSent();
        $code = $this->obtainCode();
        $this->actingAs($other);
        $this->confirm($code)->assertSessionHasErrors('verification_token');
        $this->assertNull($this->record->fresh()->user_id);
    }

    public function test_professor_and_disabled_feature_cannot_confirm_school_identity(): void
    {
        $code = $this->obtainCode();
        $this->actingAs(User::factory()->create(['role' => AccountRole::Instructor]));
        $this->confirm($code)->assertForbidden();
        config(['identity.local_academic_verification_enabled' => false]);
        $this->actingAs($this->student);
        $this->confirm($code)->assertNotFound();
        $this->assertNull($this->record->fresh()->user_id);
    }
}
