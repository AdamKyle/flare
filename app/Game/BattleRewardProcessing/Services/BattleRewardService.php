<?php

namespace App\Game\BattleRewardProcessing\Services;

use Exception;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use Illuminate\Support\Facades\Log;
use App\Game\Core\Services\GoldRush;
use App\Game\Battle\Handlers\FactionHandler;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;

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

        $this->attemptToGiveShards();

        $this->characterRewardService->setCharacter($this->character)
                                     ->distributeCharacterXP($this->monster)
                                     ->distributeSkillXP($this->monster)
                                     ->giveCurrencies($this->monster);

        $this->character = $this->characterRewardService->getCharacter();

        $this->goldRush->processPotentialGoldRush($this->character, $this->monster);

        $this->updateCharacter();

        BattleItemHandler::dispatch($this->character, $this->monster);
    }


    public function updateCharacter() {
        if ($this->character->is_auto_battling && !$this->character->isLoggedIn()) {
            return;
        }

        event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
    }

    protected function handleFactionRewards() {
        if ($this->gameMap->mapType()->isPurgatory()) {
            return;
        }

        $this->factionHandler->handleFaction($this->character, $this->monster);

        $this->character = $this->character->refresh();
    }

    protected function attemptToGiveShards() {
        try {
            $this->characterRewardService->giveShards();
        } catch (Exception $e) {
            Log::error('[ERROR - Battle Reward Processsing]: Attmpting to give shards: ' . $e->getMessage());
        }
    }
}
