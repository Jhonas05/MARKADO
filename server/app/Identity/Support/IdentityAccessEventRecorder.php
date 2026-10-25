<?php

namespace App\Identity\Support;

use App\Models\StudentIdentityEvent;
use App\Models\User;

final class IdentityAccessEventRecorder
{
    public function record(User $user, string $outcome): void
    {
        $event = new StudentIdentityEvent([
            'outcome' => $outcome,
            'occurred_at' => now(),
        ]);
        $event->user()->associate($user);
        $event->save();
    }
}
