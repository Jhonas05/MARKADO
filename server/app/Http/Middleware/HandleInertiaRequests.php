<?php

namespace App\Http\Middleware;

use App\Enums\AccountRole;
use App\Gamification\Support\CharacterEconomyService;
use App\Gamification\Support\GamificationEnvironment;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
            ],
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'role' => $request->user()->role?->value,
                    'roleLabel' => $request->user()->role?->label(),
                    'accountStatus' => $request->user()->account_status?->value,
                    'accountStatusLabel' => $request->user()->account_status?->label(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'assessment' => fn () => $request->session()->get('assessment'),
            ],
            'gamification' => fn () => $this->gamification($request),
        ];
    }

    private function gamification(Request $request): array
    {
        $student = $request->user();
        if ($student?->role !== AccountRole::Student || ! GamificationEnvironment::enabled()) {
            return ['enabled' => false];
        }

        $snapshot = app(CharacterEconomyService::class)->snapshot($student);

        return [
            'enabled' => true,
            'balance' => $snapshot['balance'],
            'equippedCharacter' => $snapshot['equippedCharacter'],
        ];
    }
}
