<?php

namespace Tests\Feature;

use App\Enums\StudentVerificationStatus;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgreSqlStudentRegistrationConcurrencyTest extends TestCase
{
    public function test_simultaneous_number_email_and_retry_claims_create_one_pending_account(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires independent PostgreSQL connections.');
        }
        $schema = (string) config('database.connections.pgsql.search_path');
        $this->assertTrue(preg_match('/\Amarkado_test(?:_[a-z0-9]+)*\z/D', $schema) === 1
            || config('database.connections.pgsql.database') === 'markado_ci');
        $record = SchoolStudentRecord::query()->create([
            'student_number' => 'SYN-REG-PARALLEL', 'institutional_email' => 'parallel.reg@students.example.edu',
            'is_active' => true, 'verification_status' => StudentVerificationStatus::Verified, 'verified_at' => now(),
        ]);
        $barrier = tempnam(sys_get_temp_dir(), 'markado-register-');
        unlink($barrier);
        $processes = [];
        $jobs = [
            [$record->student_number, $record->institutional_email],
            [$record->student_number, $record->institutional_email],
            [$record->student_number, 'other@students.example.edu'],
            ['UNKNOWN-NUMBER', $record->institutional_email],
        ];
        try {
            foreach ($jobs as $index => [$number, $email]) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent_assessment_worker.php'),
                    'register_student', '0', json_encode(['student_number' => $number, 'email' => $email]), $barrier, (string) $index], base_path());
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
            $created = 0;
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $created += (int) json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['created'];
            }
            $this->assertSame(1, $created);
            $this->assertSame(1, User::query()->where('email', $record->institutional_email)->count());
            $this->assertNotNull($record->fresh()->user_id);
            $this->assertNull($record->fresh()->linked_at);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            $studentId = $record->fresh()->user_id;
            $record->delete();
            DB::table('student_identity_events')->where('user_id', $studentId)->delete();
            User::query()->whereKey($studentId)->delete();
            @unlink($barrier);
            foreach (array_keys($jobs) as $index) {
                @unlink($barrier.'.'.$index.'.ready');
            }
        }
    }
}
