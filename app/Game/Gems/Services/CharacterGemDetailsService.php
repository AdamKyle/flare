<?php

namespace App\Game\Gems\Services;

use App\Flare\Models\Gem;
use App\Game\Gems\Contracts\CharacterGemDetails;
use App\Game\Gems\Values\CharacterGemSnapshot;

class CharacterGemDetailsService implements CharacterGemDetails
{
    /**
     * Return factual snapshots for the given character-domain Gems.
     *
     * @param array $gemIds
     * @return array
     */
    public function forGemIds(array $gemIds): array
    {
        if (empty($gemIds)) {
            return [];
        }

        return Gem::with('characterModifiers.gameGemAbility')
            ->whereIn('id', $gemIds)
            ->get()
            ->map(fn (Gem $gem): CharacterGemSnapshot => CharacterGemSnapshot::fromGem($gem))
            ->values()
            ->all();
    }
}
