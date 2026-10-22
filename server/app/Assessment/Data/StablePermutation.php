<?php

namespace App\Assessment\Data;

final readonly class StablePermutation
{
    /** @param list<string> $questionIds @param array<string, list<string>> $choiceIds */
    public function __construct(
        public array $questionIds,
        public array $choiceIds,
        public string $version,
    ) {}
}
