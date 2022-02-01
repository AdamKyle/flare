<?php

namespace App\Game\Battle\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;

class BattleRewardProcessing {

    private $factionHandler;

    private $characterRewardService;

    private $goldRushService;

    public function __construct(FactionHandler $factionHandler, CharacterRewardService $characterRewardService, GoldRush $goldRush) {
        $this->factionHandler         = $factionHandler;
        $this->characterRewardService = $characterRewardService;
        $this->goldRushService        = $goldRush;
    }

    public function handleMonster(Character $character, Monster $monster) {
        if (!$character->map->gameMap->mapType()->isPurgatory()) {
            $this->factionHandler->handleFaction($character, $monster);
        }

        $this->characterRewardService->setCharacter($character->refresh())->distributeGoldAndXp($monster);

        $character = $this->characterRewardService->getCharacter();

        BattleItemHandler::dispatch($character, $monster);

        $this->goldRushService->processPotentialGoldRush($character, $monster);

        $character = $character->refresh();

        dump('Battle Reward Processing: ' . $character->gold);

        event(new UpdateTopBarEvent($character));
    }

}
