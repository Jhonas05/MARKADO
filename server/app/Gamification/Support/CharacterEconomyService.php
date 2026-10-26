<?php

namespace App\Gamification\Support;

use App\Enums\AccountRole;
use App\Models\Character;
use App\Models\CoinLedgerEntry;
use App\Models\StudentCharacter;
use App\Models\StudentWallet;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CharacterEconomyService
{
    public function provisionStarterCharacter(User $student): StudentCharacter
    {
        $this->assertEnabledStudent($student);

        return DB::transaction(function () use ($student): StudentCharacter {
            $starterId = collect(config('gamification.characters'))->firstWhere('starter', true)['id'] ?? null;
            $starter = Character::query()->whereKey($starterId)->where('is_active', true)->first();
            if ($starter === null) {
                throw new DomainException('The starter character catalog entry is unavailable.');
            }

            $wallet = $this->lockedWallet($student);
            $ownership = StudentCharacter::query()->firstOrNew([
                'student_id' => $student->id,
                'character_id' => $starter->id,
            ]);
            if (! $ownership->exists) {
                $ownership->forceFill([
                    'acquisition_type' => 'starter_grant',
                    'price_paid' => 0,
                    'acquired_at' => now(),
                ])->save();
            }

            if ($wallet->equipped_character_id === null) {
                $wallet->forceFill([
                    'equipped_character_id' => $starter->id,
                    'lock_version' => $wallet->lock_version + 1,
                ])->save();
            }

            return $ownership;
        }, 3);
    }

    public function purchase(User $student, Character $character): StudentCharacter
    {
        $this->assertEnabledStudent($student);

        return DB::transaction(function () use ($student, $character): StudentCharacter {
            $trustedCharacter = Character::query()->whereKey($character->id)->where('is_active', true)->lockForUpdate()->first();
            if ($trustedCharacter === null) {
                throw new DomainException('This character is not available for purchase.');
            }

            $wallet = $this->lockedWallet($student);
            if (StudentCharacter::query()
                ->where('student_id', $student->id)
                ->where('character_id', $trustedCharacter->id)
                ->lockForUpdate()
                ->exists()) {
                throw new DomainException('You already own this character.');
            }
            if ($wallet->balance < $trustedCharacter->price) {
                throw new DomainException('You do not have enough MARKADO Coins for this character.');
            }

            $newBalance = $wallet->balance - $trustedCharacter->price;
            $wallet->forceFill([
                'balance' => $newBalance,
                'lock_version' => $wallet->lock_version + 1,
            ])->save();

            $entry = new CoinLedgerEntry([
                'entry_type' => 'character_purchase',
                'amount' => -$trustedCharacter->price,
                'balance_after' => $newBalance,
                'occurred_at' => now(),
            ]);
            $entry->student()->associate($student);
            $entry->character()->associate($trustedCharacter);
            $entry->save();

            $ownership = new StudentCharacter([
                'acquisition_type' => 'shop_purchase',
                'price_paid' => $trustedCharacter->price,
                'acquired_at' => now(),
            ]);
            $ownership->student()->associate($student);
            $ownership->character()->associate($trustedCharacter);
            $ownership->save();

            return $ownership;
        }, 3);
    }

    public function equip(User $student, Character $character): StudentWallet
    {
        $this->assertEnabledStudent($student);

        return DB::transaction(function () use ($student, $character): StudentWallet {
            $trustedCharacter = Character::query()
                ->whereKey($character->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if ($trustedCharacter === null) {
                throw new DomainException('This character is not available to equip.');
            }

            $wallet = $this->lockedWallet($student);
            $owned = StudentCharacter::query()
                ->where('student_id', $student->id)
                ->where('character_id', $trustedCharacter->id)
                ->lockForUpdate()
                ->exists();
            if (! $owned) {
                throw new DomainException('Only an owned character can be equipped.');
            }

            $wallet->forceFill([
                'equipped_character_id' => $trustedCharacter->id,
                'lock_version' => $wallet->lock_version + 1,
            ])->save();

            return $wallet->fresh('equippedCharacter');
        }, 3);
    }

    public function snapshot(User $student): array
    {
        $wallet = StudentWallet::query()->with('equippedCharacter')->find($student->id);
        $owned = StudentCharacter::query()->where('student_id', $student->id)->pluck('character_id')->all();

        return [
            'balance' => $wallet?->balance ?? 0,
            'completionReward' => (int) config('gamification.completion_reward'),
            'equippedCharacter' => $wallet?->equippedCharacter === null ? null : $this->characterProjection(
                $wallet->equippedCharacter,
                true,
                true,
            ),
            'characters' => Character::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->orderBy('name')
                ->get()
                ->map(fn (Character $character): array => $this->characterProjection(
                    $character,
                    in_array($character->id, $owned, true),
                    $wallet?->equipped_character_id === $character->id,
                ))
                ->values()
                ->all(),
        ];
    }

    private function characterProjection(Character $character, bool $owned, bool $equipped): array
    {
        return [
            'id' => $character->id,
            'slug' => $character->slug,
            'name' => $character->name,
            'description' => $character->description,
            'price' => $character->price,
            'artworkPath' => $character->artwork_path,
            'owned' => $owned,
            'equipped' => $equipped,
        ];
    }

    private function lockedWallet(User $student): StudentWallet
    {
        StudentWallet::query()->insertOrIgnore([
            'student_id' => $student->id,
            'balance' => 0,
            'lock_version' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StudentWallet::query()->lockForUpdate()->findOrFail($student->id);
    }

    private function assertEnabledStudent(User $student): void
    {
        if (! GamificationEnvironment::enabled()) {
            throw new DomainException('Gamification is not enabled in this environment.');
        }
        $this->assertStudent($student);
    }

    private function assertStudent(User $student): void
    {
        if ($student->role !== AccountRole::Student) {
            throw new DomainException('Only Student accounts have a character collection.');
        }
    }
}
