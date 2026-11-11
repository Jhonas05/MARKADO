<?php

namespace App\Identity\Support;

use App\Models\SchoolStudentRecord;

final class TrustedRosterRecordPolicy
{
    public function accepts(SchoolStudentRecord $record): bool
    {
        if (LocalRegistrationEnvironment::enabled()) {
            return true;
        }

        if (! ProductionRegistrationEnvironment::enabled()
            && ! HostedPresentationRegistrationEnvironment::enabled()) {
            return false;
        }

        $source = config('identity.trusted_roster_source');
        $maxAge = config('identity.trusted_roster_max_age_hours');

        return is_string($source)
            && hash_equals($source, (string) $record->roster_source)
            && is_int($maxAge)
            && $record->roster_synced_at !== null
            && $record->roster_synced_at->gte(now()->subHours($maxAge));
    }
}
