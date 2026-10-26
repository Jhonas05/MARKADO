<?php

namespace App\Gamification\Support;

use App\Models\Character;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CharacterCatalogSynchronizer
{
    /** @return Collection<int, Character> */
    public function sync(): Collection
    {
        return DB::transaction(function () {
            foreach (config('gamification.characters', []) as $configured) {
                if (($configured['price'] ?? -1) < 0) {
                    throw new DomainException('Character prices must be nonnegative.');
                }

                Character::query()->updateOrCreate(
                    ['id' => $configured['id']],
                    [
                        'slug' => $configured['slug'],
                        'name' => $configured['name'],
                        'description' => $configured['description'],
                        'price' => $configured['price'],
                        'artwork_path' => $configured['artwork_path'],
                        'is_active' => true,
                    ],
                );
            }

            return Character::query()->where('is_active', true)->orderBy('price')->orderBy('name')->get();
        });
    }
}
