<?php

namespace App\Assessment\Data;

final readonly class TimerPlan
{
    public function __construct(
        public ?int $wholeQuizSeconds,
        public ?int $perQuestionSeconds,
    ) {}
}
