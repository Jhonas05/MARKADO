<?php

namespace App\Assessment\Data;

final readonly class QuizPreflightResult
{
    /**
     * @param  array{id: string, title: string, courseCode: string, courseName: ?string,
     *     instructions: ?string, questionCount: int, displayTimezone: string}|null  $quiz
     */
    public function __construct(
        public string $state,
        public string $message,
        public ?array $quiz = null,
    ) {}

    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'message' => $this->message,
            'quiz' => $this->quiz,
            'canStart' => false,
        ];
    }
}
