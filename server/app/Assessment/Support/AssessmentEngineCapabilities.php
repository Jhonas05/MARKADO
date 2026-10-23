<?php

namespace App\Assessment\Support;

final class AssessmentEngineCapabilities
{
    /**
     * These flags are intentionally false. Interfaces exist for implementation
     * seams, but no assessment behavior is activated without policy sign-off.
     */
    public function toArray(): array
    {
        return [
            'attemptCreation' => ['enabled' => false, 'blockers' => ['D04', 'D06', 'D08', 'D09']],
            'timers' => ['enabled' => false, 'blockers' => ['D04', 'D05', 'D06']],
            'answerSaving' => ['enabled' => false, 'blockers' => ['D04', 'D05', 'D06', 'D09']],
            'submission' => ['enabled' => false, 'blockers' => ['D04', 'D06', 'D09']],
            'hardCloseFinalization' => ['enabled' => false, 'blockers' => ['D04', 'D06', 'D08', 'D09']],
            'scoring' => ['enabled' => false, 'blockers' => ['D09', 'D12']],
            'resultRelease' => ['enabled' => false, 'blockers' => ['D09', 'D12']],
        ];
    }
}
