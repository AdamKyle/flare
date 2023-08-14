<?php

namespace App\Game\Battle\Services;

use Exception;
use App\Flare\Models\Map;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use Illuminate\Support\Facades\Log;
use App\Game\Core\Services\GoldRush;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Jobs\CharacterUpdateJob;
use App\Flare\Services\CharacterRewardService;

class BattleRewardProcessing {

    use MercenaryBonus;

    private FactionHandler $factionHandler;

    private CharacterRewardService $characterRewardService;

    private GoldRush $goldRushService;

    public function __construct(FactionHandler $factionHandler, CharacterRewardService $characterRewardService, GoldRush $goldRush) {
        $this->factionHandler         = $factionHandler;
        $this->characterRewardService = $characterRewardService;
        $this->goldRushService        = $goldRush;
    }

    public function handleMonster(Character $character, Monster $monster) {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        if (!$gameMap->mapType()->isPurgatory()) {
            $this->factionHandler->handleFaction($character, $monster);

            $character = $character->refresh();
        }

        $characterRewardService = $this->characterRewardService->setCharacter($character)
                                       ->distributeCharacterXP($monster)
                                       ->distributeSkillXP($monster)
                                       ->giveCurrencies($monster);

        try {
            $characterRewardService = $characterRewardService->giveShards();
        } catch (Exception $e) {
            Log::error('[ERROR - Battle Reward Processsing]: Attmpting to give shards: ' . $e->getMessage());
        }

        $characterRewardService->currencyEventReward($monster);

        $character = $this->characterRewardService->getCharacter();

        $this->goldRushService->processPotentialGoldRush($character, $monster);

        if ($character->is_auto_battling) {
            $character = $character->refresh();

            CharacterUpdateJob::dispatch($character);
        }

        BattleItemHandler::dispatch($character, $monster);
    }

}
