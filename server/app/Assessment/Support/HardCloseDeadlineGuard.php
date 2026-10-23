<?php

namespace App\Assessment\Support;

use App\Assessment\Contracts\AuthoritativeClock;
use App\Assessment\Data\AttemptDeadlinePlan;
use DateTimeImmutable;
use DomainException;

final class HardCloseDeadlineGuard
{
    public function __construct(private readonly AuthoritativeClock $clock) {}

    public function mayStart(DateTimeImmutable $quizClosesAt): bool
    {
        return $this->clock->now() < $quizClosesAt;
    }

    public function planAttempt(
        DateTimeImmutable $startedAt,
        ?int $configuredQuizDurationSeconds,
        DateTimeImmutable $quizClosesAt,
    ): AttemptDeadlinePlan {
        if ($startedAt >= $quizClosesAt) {
            throw new DomainException('An attempt cannot start at or after the quiz closing timestamp.');
        }

        if ($configuredQuizDurationSeconds !== null && $configuredQuizDurationSeconds < 1) {
            throw new DomainException('A configured quiz duration must be at least one second.');
        }

        $sessionDeadlineAt = $configuredQuizDurationSeconds === null
            ? null
            : $startedAt->modify("+{$configuredQuizDurationSeconds} seconds");
        $effectiveDeadlineAt = $sessionDeadlineAt !== null && $sessionDeadlineAt < $quizClosesAt
            ? $sessionDeadlineAt
            : $quizClosesAt;

        return new AttemptDeadlinePlan(
            $startedAt,
            $quizClosesAt,
            $sessionDeadlineAt,
            $effectiveDeadlineAt,
        );
    }

    public function capQuestionDeadline(
        DateTimeImmutable $questionDeadlineAt,
        DateTimeImmutable $effectiveAttemptDeadlineAt,
    ): DateTimeImmutable {
        return $questionDeadlineAt < $effectiveAttemptDeadlineAt
            ? $questionDeadlineAt
            : $effectiveAttemptDeadlineAt;
    }

    public function hasReached(DateTimeImmutable $deadlineAt): bool
    {
        return $this->clock->now() >= $deadlineAt;
    }
}
