<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegacyStudentSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_signup_flag_cannot_expose_registration_without_email_verification(): void
    {
        config(['identity.unverified_student_signup_enabled' => true]);
        Route::middleware('web')->group(base_path('routes/web.php'));

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('accountPolicies.publicStudentRegistration', false));
        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('registration.available', false));
        $this->post('/register', [
            'student_number' => 'SYN-LEGACY',
            'email' => 'legacy@students.example.edu',
            'password' => 'Synthetic-Legacy-Password-1000',
            'password_confirmation' => 'Synthetic-Legacy-Password-1000',
        ])->assertStatus(405);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_legacy_student_and_unverified_claim_are_preserved(): void
    {
        $student = User::factory()->create([
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => null,
            'password' => Hash::make('Synthetic-Legacy-Password-1000'),
        ]);
        DB::table('student_self_reported_claims')->insert([
            'user_id' => $student->id, 'student_number' => 'SYN-LEGACY',
        ]);

        $this->post('/login', ['email' => $student->email, 'password' => 'Synthetic-Legacy-Password-1000'])
            ->assertRedirect('/student/dashboard');
        $this->get('/student/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('eligibility.verificationLabel', 'Self-reported (unverified)')
            ->where('eligibility.mailboxVerified', false)
            ->where('eligibility.identityMatched', false));
        $this->assertDatabaseHas('student_self_reported_claims', ['user_id' => $student->id, 'student_number' => 'SYN-LEGACY']);
    }
}
