<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UnverifiedStudentRegistrationService
{
    /**
     * Create a Student login without asserting school identity or email ownership.
     * A self-reported number is never inserted into the trusted school roster.
     */
    public function register(string $studentNumber, string $email, #[\SensitiveParameter] string $password): ?User
    {
        if (! UnverifiedStudentSignupEnvironment::enabled()
            || trim($studentNumber) !== $studentNumber) {
            return null;
        }

        $email = trim($email);
        $passwordHash = Hash::make($password);

        try {
            return DB::transaction(function () use ($studentNumber, $email, $passwordHash): ?User {
                // Serialize email claims across both registration modes on PostgreSQL.
                if (DB::getDriverName() === 'pgsql') {
                    DB::select('select pg_advisory_xact_lock(hashtextextended(?, 0))', [
                        hash_hmac('sha256', 'registration-email:'.strtolower($email), (string) config('app.key')),
                    ]);
                }

                if (User::query()->whereRaw('lower(email) = ?', [strtolower($email)])->exists()) {
                    return null;
                }

                $student = User::query()->create([
                    'name' => 'Student',
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => AccountRole::Student,
                    'account_status' => AccountStatus::Active,
                ]);

                DB::table('student_self_reported_claims')->insert([
                    'user_id' => $student->id,
                    'student_number' => $studentNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                app(IdentityAccessEventRecorder::class)->record($student, 'student_self_signup_unverified');

                return $student;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
