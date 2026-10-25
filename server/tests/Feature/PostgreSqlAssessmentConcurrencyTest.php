<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AttemptSessionManager;
use App\Enums\AccountRole;
use App\Models\AttemptResult;
use App\Models\QuizAttempt;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\TestCase;

#[Group('postgresql')]
class PostgreSqlAssessmentConcurrencyTest extends TestCase
{
    use BuildsAssessmentFixtures;

    public function test_disposable_schema_guard_rejects_protected_and_ambiguous_search_paths(): void
    {
        foreach ([
            'markado_test',
            'markado_test_v8_20261025',
            'markado_v7_assessment_20261024',
            'markado_v8_rehearsal_20261024_1024',
        ] as $schema) {
            $this->assertTrue($this->isDisposableTestSchema($schema), "{$schema} should be accepted.");
        }

        foreach ([
            'public',
            'markado_v8_rehearsal_',
            'markado_v8_rehearsal_safe,public',
            'markado_test,public',
            'markado_v8_rehearsal_safe public',
        ] as $schema) {
            $this->assertFalse($this->isDisposableTestSchema($schema), "{$schema} should be rejected.");
        }
    }

    public function test_parallel_start_and_terminal_requests_share_one_attempt_and_receipt(): void
    {
        $this->assertSafePostgreSqlIsolation();
        $this->assertTrue(Schema::hasTable('quiz_attempts'));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        [, $student, $quiz] = $this->assessmentFixture(
            randomize: false,
            opensAt: $now->modify('-5 minutes'),
            closesAt: $now->modify('+1 hour'),
        );
        $saveRaceFixture = null;

        try {
            $starts = $this->runConcurrently([
                ['start', (string) $student->id, $quiz->id],
                ['start', (string) $student->id, $quiz->id],
            ]);

            $this->assertSame($starts[0]['attempt_id'], $starts[1]['attempt_id']);
            $this->assertEqualsCanonicalizing([false, true], array_column($starts, 'resumed'));
            $this->assertSame(1, QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('student_id', $student->id)
                ->count());

            $attempt = QuizAttempt::query()->findOrFail($starts[0]['attempt_id']);
            $attempt->forceFill([
                'session_deadline_at' => $now->modify('-5 minutes'),
                'effective_deadline_at' => $now->modify('-5 minutes'),
            ])->save();

            $finalizations = $this->runConcurrently([
                ['submit', $attempt->id, '-'],
                ['expire', $attempt->id, '-'],
            ]);

            $this->assertSame($finalizations[0]['receipt_id'], $finalizations[1]['receipt_id']);
            $this->assertEqualsCanonicalizing([false, true], array_column($finalizations, 'already_finalized'));
            $this->assertEqualsCanonicalizing(
                ['session_expired', 'session_expired'],
                array_column($finalizations, 'reason'),
            );
            $this->assertSame(1, AttemptResult::query()->where('attempt_id', $attempt->id)->count());
            $this->assertSame('submitted', $attempt->fresh()->status);

            [, $raceStudent, $raceQuiz] = $this->assessmentFixture(
                randomize: false,
                opensAt: $now->modify('-5 minutes'),
                closesAt: $now->modify('+1 hour'),
            );
            $saveRaceFixture = [$raceQuiz->id, $raceStudent->id, $raceQuiz->owner_id];
            $raceAttemptId = app(AttemptSessionManager::class)
                ->resumeOrCreate($raceStudent, $raceQuiz)
                ->attemptId;
            $raceAttempt = QuizAttempt::query()->with('questionSnapshots')->findOrFail($raceAttemptId);
            $raceSnapshot = $raceAttempt->questionSnapshots->firstOrFail();
            $raceAttempt->forceFill([
                'session_deadline_at' => $now->modify('-5 minutes'),
                'effective_deadline_at' => $now->modify('-5 minutes'),
            ])->save();
            $raceAttempt->questionStates()
                ->where('status', 'active')
                ->update(['deadline_at' => $now->modify('-5 minutes')]);

            $saveRace = $this->runConcurrently([
                ['save', $raceAttempt->id, json_encode([
                    'question_snapshot_id' => $raceSnapshot->id,
                    'selected_choice_id' => $raceSnapshot->choices[0]['id'],
                    'idempotency_key' => 'concurrent-late-save-01',
                    'expected_version' => 0,
                ], JSON_THROW_ON_ERROR)],
                ['expire', $raceAttempt->id, '-'],
            ]);

            $this->assertSame('rejected', $saveRace[0]['outcome']);
            $this->assertSame('session_expired', $saveRace[1]['reason']);
            $this->assertSame('submitted', $raceAttempt->fresh()->status);
            $this->assertSame(0, $raceAttempt->answers()->count());
            $this->assertSame(1, AttemptResult::query()->where('attempt_id', $raceAttempt->id)->count());
        } finally {
            if ($saveRaceFixture !== null) {
                $this->removePublishedFixture(...$saveRaceFixture);
            }
            $this->removePublishedFixture($quiz->id, $student->id, $quiz->owner_id);
        }
    }

    public function test_concurrent_duplicate_roster_identity_claims_create_only_one_record(): void
    {
        $this->assertSafePostgreSqlIsolation();

        $students = User::factory()->count(2)->create(['role' => AccountRole::Student]);
        $payload = json_encode([
            'student_number' => '0000123400',
            'institutional_email' => 'identity-claim@example.test',
        ], JSON_THROW_ON_ERROR);

        try {
            $results = $this->runConcurrently([
                ['identity_record', (string) $students[0]->id, $payload],
                ['identity_record', (string) $students[1]->id, $payload],
            ]);

            $this->assertEqualsCanonicalizing(['created', 'rejected'], array_column($results, 'outcome'));
            $this->assertSame(1, SchoolStudentRecord::query()
                ->where('student_number', '0000123400')
                ->where('institutional_email', 'identity-claim@example.test')
                ->count());
        } finally {
            SchoolStudentRecord::query()->whereIn('user_id', $students->modelKeys())->delete();
            User::query()->whereKey($students->modelKeys())->delete();
        }
    }

    /**
     * @param  list<array{string, string, string}>  $jobs
     * @return list<array<string, mixed>>
     */
    private function runConcurrently(array $jobs): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'markado-assessment-barrier-');
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

    private function isDisposableTestSchema(string $schema): bool
    {
        return preg_match(
            '/^markado_(?:test(?:_[a-z0-9]+)*|v\d+_(?:assessment|rehearsal)(?:_[a-z0-9]+)+)$/D',
            $schema,
        ) === 1;
    }

    private function assertSafePostgreSqlIsolation(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This test requires independent PostgreSQL connections.');
        }

        $schema = (string) config('database.connections.pgsql.search_path');
        $database = (string) config('database.connections.pgsql.database');
        $this->assertTrue(
            $this->isDisposableTestSchema($schema) || $database === 'markado_ci',
            'Concurrency testing is restricted to a disposable MARKADO test schema or CI database.',
        );
    }

    private function removePublishedFixture(string $quizId, int $studentId, int $ownerId): void
    {
        DB::statement('ALTER TABLE quiz_revisions DISABLE TRIGGER guard_published_quiz_revision');
        DB::statement('ALTER TABLE quiz_revision_questions DISABLE TRIGGER guard_published_revision_questions');
        DB::statement('ALTER TABLE quiz_revision_choices DISABLE TRIGGER guard_published_revision_choices');

        try {
            DB::transaction(function () use ($quizId, $studentId, $ownerId): void {
                DB::table('quizzes')->where('id', $quizId)->update([
                    'current_published_revision_id' => null,
                    'active_draft_revision_id' => null,
                ]);
                DB::table('quiz_attempts')->where('quiz_id', $quizId)->delete();
                DB::table('quizzes')->where('id', $quizId)->delete();
                DB::table('school_student_records')->where('user_id', $studentId)->delete();
                DB::table('student_identity_events')->whereIn('user_id', [$studentId, $ownerId])->delete();
                DB::table('users')->whereIn('id', [$studentId, $ownerId])->delete();
            });
        } finally {
            DB::statement('ALTER TABLE quiz_revision_choices ENABLE TRIGGER guard_published_revision_choices');
            DB::statement('ALTER TABLE quiz_revision_questions ENABLE TRIGGER guard_published_revision_questions');
            DB::statement('ALTER TABLE quiz_revisions ENABLE TRIGGER guard_published_quiz_revision');
        }
    }
}
