<?php

use App\Assessment\Contracts\AnswerStore;
use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Data\AnswerSaveCommand;
use App\Identity\Contracts\StudentActivationSecurityGate;
use App\Identity\Contracts\StudentActivationService;
use App\Identity\Data\StudentIdentityDecision;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $action, $subjectId, $relatedId, $barrier, $worker] = $argv;
$ready = $barrier.'.'.$worker.'.ready';
touch($ready);

$deadline = microtime(true) + 15;
while (! file_exists($barrier) && microtime(true) < $deadline) {
    usleep(10_000);
}

if (! file_exists($barrier)) {
    fwrite(STDERR, 'Concurrency barrier was not released.');
    exit(2);
}

try {
    if ($action === 'activate_student') {
        $payload = json_decode($relatedId, true, flags: JSON_THROW_ON_ERROR);
        config()->set('identity.institutional_email_domains', ['students.example.edu']);
        config()->set('identity.student_self_registration_enabled', true);
        config()->set('identity.automatic_student_activation_enabled', true);
        $app->instance(StudentActivationSecurityGate::class, new class implements StudentActivationSecurityGate
        {
            public function evaluate(User $student, string $rosterRecordId): StudentIdentityDecision
            {
                return StudentIdentityDecision::matched();
            }
        });

        $result = $app->make(StudentActivationService::class)->activate(
            User::query()->findOrFail($subjectId),
            $payload['student_number'],
            $payload['institutional_email'],
        );
        echo json_encode([
            'successful' => $result->successful,
            'changed' => $result->changed,
            'reason' => $result->reason,
        ], JSON_THROW_ON_ERROR);
        exit(0);
    }

    if ($action === 'identity_record') {
        $payload = json_decode($relatedId, true, flags: JSON_THROW_ON_ERROR);

        try {
            $record = new SchoolStudentRecord([
                'student_number' => $payload['student_number'],
                'institutional_email' => $payload['institutional_email'],
                'verification_status' => 'unverified',
                'is_active' => true,
            ]);
            $record->user()->associate(User::query()->findOrFail($subjectId));
            $record->save();
            echo json_encode(['outcome' => 'created'], JSON_THROW_ON_ERROR);
        } catch (QueryException) {
            echo json_encode(['outcome' => 'rejected'], JSON_THROW_ON_ERROR);
        }
        exit(0);
    }

    if ($action === 'start') {
        $session = $app->make(AttemptSessionManager::class)->resumeOrCreate(
            User::query()->findOrFail($subjectId),
            Quiz::query()->findOrFail($relatedId),
        );
        echo json_encode([
            'attempt_id' => $session->attemptId,
            'resumed' => $session->resumed,
            'status' => $session->status,
        ], JSON_THROW_ON_ERROR);
        exit(0);
    }

    if ($action === 'save') {
        $payload = json_decode($relatedId, true, flags: JSON_THROW_ON_ERROR);

        try {
            $acknowledgement = $app->make(AnswerStore::class)->save(new AnswerSaveCommand(
                $subjectId,
                $payload['question_snapshot_id'],
                $payload['selected_choice_id'],
                $payload['idempotency_key'],
                $payload['expected_version'],
            ));
            echo json_encode([
                'outcome' => 'saved',
                'version' => $acknowledgement->version,
            ], JSON_THROW_ON_ERROR);
        } catch (DomainException $exception) {
            echo json_encode([
                'outcome' => 'rejected',
                'reason' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR);
        }
        exit(0);
    }

    $attempt = QuizAttempt::query()->findOrFail($subjectId);
    $finalizer = $app->make(AttemptFinalizer::class);
    $receipt = $action === 'submit'
        ? $finalizer->submitByStudent($attempt)
        : $finalizer->finalizeAtEffectiveDeadline($attempt);

    echo json_encode([
        'receipt_id' => $receipt->receiptId,
        'reason' => $receipt->reason,
        'already_finalized' => $receipt->alreadyFinalized,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(1);
}
