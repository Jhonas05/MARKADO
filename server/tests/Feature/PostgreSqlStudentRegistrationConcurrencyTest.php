<?php

namespace Tests\Feature;

use App\Enums\StudentVerificationStatus;
use App\Models\SchoolStudentRecord;
use App\Models\StudentSignupClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgreSqlStudentRegistrationConcurrencyTest extends TestCase
{
    public function test_simultaneous_number_email_and_retry_claims_create_one_pending_account(): void
    {
        $this->assertDisposablePostgreSql();
        $record = SchoolStudentRecord::query()->create([
            'student_number' => 'SYN-REG-PARALLEL', 'institutional_email' => 'parallel.reg@students.example.edu',
            'is_active' => true, 'verification_status' => StudentVerificationStatus::Verified, 'verified_at' => now(),
        ]);
        $jobs = [
            [$record->student_number, $record->institutional_email],
            [$record->student_number, $record->institutional_email],
            [$record->student_number, 'other@students.example.edu'],
            ['UNKNOWN-NUMBER', $record->institutional_email],
        ];
        try {
            $results = $this->raceRegistration('register_student', $jobs);
            $this->assertCount(1, array_filter(array_column($results, 'created')));
            $this->assertSame(1, User::query()->where('email', $record->institutional_email)->count());
            $this->assertNotNull($record->fresh()->user_id);
            $this->assertNull($record->fresh()->linked_at);
        } finally {
            $studentId = $record->fresh()->user_id;
            $record->delete();
            DB::table('student_identity_events')->where('user_id', $studentId)->delete();
            User::query()->whereKey($studentId)->delete();
        }
    }

    public function test_simultaneous_self_service_claims_share_only_the_same_mailbox_account(): void
    {
        $this->assertDisposablePostgreSql();
        $first = 'parallel.signup@students.example.edu';
        $second = 'parallel.other@students.example.edu';
        try {
            $results = $this->raceRegistration('signup_student', [
                ['SYN-SIGNUP-ONE', $first],
                ['SYN-SIGNUP-TWO', strtoupper($first)],
                ['SYN-SIGNUP-ONE', $second],
            ]);
            $this->assertCount(3, array_filter(array_column($results, 'created')));
            $this->assertSame($results[0]['user_id'], $results[1]['user_id']);
            $this->assertNotSame($results[0]['user_id'], $results[2]['user_id']);
            $this->assertSame(2, User::query()->whereIn('email', [$first, $second])->count());
            $this->assertSame(2, StudentSignupClaim::query()->whereKey(array_column($results, 'user_id'))->count());
            $this->assertFalse(SchoolStudentRecord::query()->whereIn('user_id', array_column($results, 'user_id'))->exists());
        } finally {
            $ids = User::query()->whereIn('email', [$first, $second])->pluck('id');
            DB::table('student_identity_events')->whereIn('user_id', $ids)->delete();
            User::query()->whereIn('id', $ids)->delete();
        }
    }

    private function assertDisposablePostgreSql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires independent PostgreSQL connections.');
        }
        $schema = (string) config('database.connections.pgsql.search_path');
        $this->assertTrue(preg_match('/\Amarkado_test(?:_[a-z0-9]+)*\z/D', $schema) === 1
            || config('database.connections.pgsql.database') === 'markado_ci');
    }

    private function raceRegistration(string $action, array $jobs): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'markado-register-');
        unlink($barrier);
        $processes = [];
        try {
            foreach ($jobs as $index => [$number, $email]) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent_assessment_worker.php'),
                    $action, '0', json_encode(['student_number' => $number, 'email' => $email]), $barrier, (string) $index], base_path());
                $process->setTimeout(45);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 20;
            do {
                $ready = true;
                foreach (array_keys($jobs) as $index) {
                    $ready = $ready && file_exists($barrier.'.'.$index.'.ready');
                }
                if (! $ready) {
                    usleep(10000);
                }
            } while (! $ready && microtime(true) < $deadline);
            $this->assertTrue($ready);
            touch($barrier);
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            @unlink($barrier);
            foreach (array_keys($jobs) as $index) {
                @unlink($barrier.'.'.$index.'.ready');
            }
        }
    }
}
