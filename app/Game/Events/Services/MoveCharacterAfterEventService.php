<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Maps\Services\TraverseService;
use Illuminate\Database\Eloquent\Collection;

class MoveCharacterAfterEventService
{
    public function __construct(
        private readonly TraverseService $traverse,
        private readonly ExplorationAutomationService $exploration
    ) {}

    /**
     * @param  callable  $callback  function(Collection $characters): void
     */
    public function forCharactersOnMap(int $mapId, callable $callback): void
    {
        Character::select('characters.*')
            ->join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $mapId)
            ->chunk(100, function ($characters) use ($callback) {
                $callback($characters);
            });
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    public function stopExplorationFor(Collection $characters): void
    {
        if ($characters->isEmpty()) {
            return;
        }

        foreach ($characters as $character) {
            $this->exploration->stopExploration($character);
        }
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    public function resetFactionProgressForMap(Collection $characters, int $mapId): void
    {
        if ($characters->isEmpty()) {
            return;
        }

        foreach ($characters as $character) {
            $character->factions()
                ->where('game_map_id', $mapId)
                ->update([
                    'current_level' => 0,
                    'current_points' => 0,
                    'points_needed' => \App\Game\Core\Values\FactionLevel::getPointsNeeded(0),
                    'maxed' => false,
                    'title' => null,
                ]);
        }
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    public function moveAllToSurface(Collection $characters, GameMap $surface): void
    {
        if ($characters->isEmpty()) {
            return;
        }

        foreach ($characters as $character) {
            $this->traverse->travel($surface->id, $character);
        }
    }
}
