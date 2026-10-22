<?php

namespace App\Assessment\Data;

final readonly class ScoreSummary
{
    public function __construct(
        public int $earnedPoints,
        public int $availablePoints,
        public string $scoringVersion,
    ) {}
}
