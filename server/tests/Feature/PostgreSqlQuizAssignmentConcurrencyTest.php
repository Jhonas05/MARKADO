<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\QuizStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group('postgresql')]
class PostgreSqlQuizAssignmentConcurrencyTest extends TestCase
{
    public function test_parallel_assignment_and_reactivation_keep_one_active_record(): void
    {
        $this->assertSafePostgreSqlIsolation();

        $owner = User::factory()->create(['role' => AccountRole::Instructor]);
        $student = User::factory()->create([
            'role' => AccountRole::Student,
            'email' => 'parallel.assignment@students.example.edu',
            'email_verified_at' => now(),
        ]);
        $quiz = new Quiz([
            'title' => 'Parallel assignment test',
            'course_code' => 'SYN 931',
        ]);
        $quiz->owner()->associate($owner);
        $quiz->status = QuizStatus::Draft;
        $quiz->access_code = Str::upper(Str::random(8));
        $quiz->display_timezone = 'Asia/Manila';
        $quiz->save();
        $record = new SchoolStudentRecord([
            'student_number' => 'SYN-PARALLEL-9301',
            'institutional_email' => $student->email,
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_at' => now(),
            'linked_at' => now(),
        ]);
        $record->user()->associate($student);
        $record->verifiedBy()->associate($owner);
        $record->save();

        $payload = json_encode([
            'quiz_id' => $quiz->id,
            'student_number' => $record->student_number,
        ], JSON_THROW_ON_ERROR);

        try {
            $initial = $this->runConcurrently([
                ['assign_student', (string) $owner->id, $payload],
                ['assign_student', (string) $owner->id, $payload],
            ]);

            $this->assertEqualsCanonicalizing(['assigned', 'already_assigned'], array_column($initial, 'outcome'));
            $this->assertSame($initial[0]['assignment_id'], $initial[1]['assignment_id']);
            $this->assertSame(1, QuizStudentAssignment::query()->count());

            $assignment = QuizStudentAssignment::query()->sole();
            $assignment->forceFill([
                'is_active' => false,
                'deactivated_by' => $owner->id,
                'deactivated_at' => now(),
            ])->save();

            $reactivation = $this->runConcurrently([
                ['assign_student', (string) $owner->id, $payload],
                ['assign_student', (string) $owner->id, $payload],
            ]);

            $this->assertEqualsCanonicalizing(['reactivated', 'already_assigned'], array_column($reactivation, 'outcome'));
            $this->assertSame($assignment->id, $reactivation[0]['assignment_id']);
            $this->assertSame($assignment->id, $reactivation[1]['assignment_id']);
            $this->assertSame(1, QuizStudentAssignment::query()->count());
            $this->assertTrue($assignment->fresh()->is_active);
        } finally {
            DB::transaction(function () use ($quiz, $record, $student, $owner): void {
                QuizStudentAssignment::query()->where('quiz_id', $quiz->id)->delete();
                DB::table('student_identity_events')->where('user_id', $student->id)->delete();
                SchoolStudentRecord::query()->whereKey($record->id)->delete();
                Quiz::query()->whereKey($quiz->id)->delete();
                User::query()->whereIn('id', [$student->id, $owner->id])->delete();
            });
        }
    }

    /**
     * @param  list<array{string, string, string}>  $jobs
     * @return list<array<string, mixed>>
     */
    private function runConcurrently(array $jobs): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'markado-assignment-barrier-');
        if ($barrier === false) {
            self::fail('Unable to allocate the concurrency barrier.');
        }
        unlink($barrier);

        $processes = [];
        foreach ($jobs as $index => [$action, $subjectId, $relatedId]) {
            $process = new Process([
                PHP_BINARY,
                base_path('tests/Support/concurrent_assessment_worker.php'),
                $action,
                $subjectId,
                $relatedId,
                $barrier,
                (string) $index,
            ], base_path());
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }

        $readyDeadline = microtime(true) + 15;
        do {
            $ready = true;
            foreach (array_keys($jobs) as $index) {
                $ready = $ready && file_exists($barrier.'.'.$index.'.ready');
            }
            if (! $ready) {
                usleep(10_000);
            }
        } while (! $ready && microtime(true) < $readyDeadline);

        $this->assertTrue($ready, 'Concurrent workers did not reach the barrier.');
        touch($barrier);

        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }

        @unlink($barrier);
        foreach (array_keys($jobs) as $index) {
            @unlink($barrier.'.'.$index.'.ready');
        }

        return $results;
    }

    private function assertSafePostgreSqlIsolation(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This test requires independent PostgreSQL connections.');
        }

        $schema = (string) config('database.connections.pgsql.search_path');
        $database = (string) config('database.connections.pgsql.database');
        $safeSchema = preg_match(
            '/^markado_(?:test(?:_[a-z0-9]+)*|v\d+_(?:assessment|rehearsal)(?:_[a-z0-9]+)+)$/D',
            $schema,
        ) === 1;

        $this->assertTrue(
            $safeSchema || $database === 'markado_ci',
            'Concurrency testing is restricted to a disposable MARKADO test schema or CI database.',
        );
    }
}
