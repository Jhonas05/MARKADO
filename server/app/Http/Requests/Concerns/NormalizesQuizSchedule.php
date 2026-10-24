<?php

namespace App\Http\Requests\Concerns;

use Carbon\CarbonImmutable;

trait NormalizesQuizSchedule
{
    public function validatedForPersistence(): array
    {
        $data = $this->validated();

        foreach (['opens_at', 'closes_at'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = CarbonImmutable::createFromFormat(
                    'Y-m-d\TH:i',
                    $data[$field],
                    config('assessment.display_timezone'),
                )->utc();
            }
        }

        return $data;
    }

    protected function scheduleRules(): array
    {
        return [
            'opens_at' => ['nullable', 'required_with:closes_at', 'date_format:Y-m-d\TH:i'],
            'closes_at' => ['nullable', 'required_with:opens_at', 'date_format:Y-m-d\TH:i', 'after:opens_at'],
            'whole_quiz_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'per_question_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'randomize_questions' => ['sometimes', 'boolean'],
            'randomize_choices' => ['sometimes', 'boolean'],
        ];
    }
}
