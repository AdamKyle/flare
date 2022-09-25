<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use Exception;

class BattleRewardProcessing {

    private FactionHandler $factionHandler;

    private CharacterRewardService $characterRewardService;

    private GoldRush $goldRushService;

    public function __construct(FactionHandler $factionHandler, CharacterRewardService $characterRewardService, GoldRush $goldRush) {
        $this->factionHandler         = $factionHandler;
        $this->characterRewardService = $characterRewardService;
        $this->goldRushService        = $goldRush;
    }

    public function handleMonster(Character $character, Monster $monster, bool $isAutomation = false) {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        if (!$gameMap->mapType()->isPurgatory()) {
            $this->factionHandler->handleFaction($character, $monster);
        }

        $this->characterRewardService->setCharacter($character->refresh())->distributeGoldAndXp($monster);

        $character = $this->characterRewardService->getCharacter();

        BattleItemHandler::dispatch($character, $monster);

        $this->goldRushService->processPotentialGoldRush($character, $monster);

        $character = $this->giveShards($character);

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Give character shards.
     *
     * - Only if they are at a special location and in gold mines.
     *
     * @param Character $character
     * @return Character
     * @throws Exception
     */
    protected function giveShards(Character $character): Character {
        $specialLocation = $this->findLocationWithEffect($character->map);

        if (!is_null($specialLocation)) {
            if (!is_null($specialLocation->type)) {
                $locationType = new LocationType($specialLocation->type);

                if ($locationType->isGoldMines()) {
                    $shards = rand(1,5);

                    $newShards = $character->shards + $shards;

                    if ($newShards > MaxCurrenciesValue::MAX_SHARDS) {
                        $newShards = MaxCurrenciesValue::MAX_SHARDS;
                    }

                    $character->update(['shards' => $newShards]);
                }
            }
        }

        return $character->refresh();
    }

    /**
     * Are we at a location with an effect (special location)?
     *
     * @param Map $map
     * @return void
     */
    protected function findLocationWithEffect(Map $map) {
        return Location::whereNotNull('enemy_strength_type')
                       ->where('x', $map->character_position_x)
                       ->where('y', $map->character_position_y)
                       ->where('game_map_id', $map->game_map_id)
                       ->first();
    }

}
