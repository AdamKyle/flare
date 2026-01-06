<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\GameMap;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Core\Values\FactionLevel;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Collection;

class FactionAssigner
{
    /**
     * Assign one faction per non-purgatory map to the character using a single bulk insert.
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $now = $state->getNow() ?? now();

        $rows = $this->buildFactionRows(
            GameMap::all(),
            $character->id,
            $now
        );

        if (! empty($rows)) {
            $character->factions()->getRelated()->newQuery()->insert($rows);
        }

        dump('Calling Next from FactionAssigner');

        return $next($state);
    }

    /**
     * Build faction insert rows for non-purgatory maps.
     */
    private function buildFactionRows(Collection $maps, int $characterId, DateTimeInterface $timestamp): array
    {
        return $maps->filter(function (GameMap $map) {
            return ! $map->mapType()->isPurgatory();
        })->map(function (GameMap $map) use ($characterId, $timestamp) {
            return [
                'character_id' => $characterId,
                'game_map_id' => $map->id,
                'points_needed' => FactionLevel::getPointsNeeded(0),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->values()->all();
    }
}
