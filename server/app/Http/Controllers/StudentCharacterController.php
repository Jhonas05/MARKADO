<?php

namespace App\Http\Controllers;

use App\Gamification\Support\CharacterEconomyService;
use App\Models\Character;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentCharacterController extends Controller
{
    public function __construct(private readonly CharacterEconomyService $economy) {}

    public function collection(Request $request): Response
    {
        return Inertia::render('Student/Gamification/Collection', [
            'economy' => $this->economy->snapshot($request->user()),
        ]);
    }

    public function shop(Request $request): Response
    {
        return Inertia::render('Student/Gamification/Shop', [
            'economy' => $this->economy->snapshot($request->user()),
        ]);
    }

    public function purchase(Request $request, Character $character): RedirectResponse
    {
        try {
            $this->economy->purchase($request->user(), $character);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['purchase' => $exception->getMessage()]);
        }

        return to_route('student.characters.collection')
            ->with('success', "{$character->name} is now in your collection.");
    }

    public function equip(Request $request, Character $character): RedirectResponse
    {
        try {
            $this->economy->equip($request->user(), $character);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['equip' => $exception->getMessage()]);
        }

        return back()->with('success', "{$character->name} is now equipped.");
    }
}
