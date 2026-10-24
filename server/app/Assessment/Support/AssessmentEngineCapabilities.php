<?php

namespace App\Assessment\Support;

final class AssessmentEngineCapabilities
{
    /**
     * Implementation, test activation, and operational activation are separate.
     */
    public function toArray(): array
    {
        $routesEnabled = (bool) config('assessment.routes_enabled');
        $expirationEnabled = (bool) config('assessment.expiration_enabled');
        $activationBlockers = $routesEnabled
            ? []
            : ['D13', 'D14', 'operations-activation-review'];

        return [
            'attemptCreation' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
            'timers' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
            'answerSaving' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
            'submission' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
            'hardCloseFinalization' => ['implemented' => true, 'enabled' => $expirationEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $expirationEnabled ? [] : ['scheduler-operations-activation']],
            'scoring' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
            'resultRelease' => ['implemented' => true, 'enabled' => $routesEnabled, 'routeEnabled' => $routesEnabled, 'blockers' => $activationBlockers],
        ];
    }
}
