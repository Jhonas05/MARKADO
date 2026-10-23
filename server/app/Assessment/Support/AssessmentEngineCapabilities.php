<?php

namespace App\Assessment\Support;

final class AssessmentEngineCapabilities
{
    /**
     * Implementation readiness is distinct from public route activation.
     * Trusted fixture services are real; unrestricted student mutations remain
     * gated until D13/D14 identity authority and the D05 final-question edge are
     * resolved and reviewed.
     */
    public function toArray(): array
    {
        return [
            'attemptCreation' => ['implemented' => true, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D13', 'D14']],
            'timers' => ['implemented' => true, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D05-final-question-edge']],
            'answerSaving' => ['implemented' => true, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D13', 'D14']],
            'submission' => ['implemented' => true, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D13', 'D14']],
            'hardCloseFinalization' => ['implemented' => true, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['scheduler-and-route-review']],
            'scoring' => ['implemented' => false, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D12']],
            'resultRelease' => ['implemented' => false, 'enabled' => false, 'routeEnabled' => false, 'blockers' => ['D11', 'D12']],
        ];
    }
}
