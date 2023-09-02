<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Game\Core\Services\GoldRush;
use App\Game\Battle\Handlers\FactionHandler;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Jobs\BattleItemHandler;

class BattleRewardService {

    private GameMap $gameMap;
    private Monster $monster;
    private Character $character;
    private FactionHandler $factionHandler;
    private CharacterRewardService $characterRewardService;
    private GoldRush $goldRush;

    public function __construct(FactionHandler $factionHandler, CharacterRewardService $characterRewardService, GoldRush $goldRush) {
        $this->factionHandler         = $factionHandler;
        $this->characterRewardService = $characterRewardService;
        $this->goldRush               = $goldRush;
    }

    public function setUp(Monster $monster, Character $character): BattleRewardService {

        $this->character = $character;
        $this->monster   = $monster;
        $this->gameMap   = $monster->gameMap;

        $this->characterRewardService->setCharacter($character);

        return $this;
    }

    public function handleBaseRewards() {
        $this->handleFactionRewards();

        $this->characterRewardService->setCharacter($this->character)
            ->distributeCharacterXP($this->monster)
            ->distributeSkillXP($this->monster)
            ->giveCurrencies($this->monster);

        $this->character = $this->characterRewardService->getCharacter();

        $this->goldRush->processPotentialGoldRush($this->character, $this->monster);

        BattleItemHandler::dispatch($this->character, $this->monster);
    }

    protected function handleFactionRewards() {
        if ($this->gameMap->mapType()->isPurgatory()) {
            return;
        }

        $this->factionHandler->handleFaction($this->character, $this->monster);

        $this->character = $this->character->refresh();
    }
}
