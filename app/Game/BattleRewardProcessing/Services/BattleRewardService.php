<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\MapNameValue;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleItemHandler;
use App\Game\Core\Services\GoldRush;
use App\Game\Events\Values\EventType;

class BattleRewardService {

    private GameMap $gameMap;
    private Monster $monster;
    private Character $character;
    private FactionHandler $factionHandler;
    private CharacterRewardService $characterRewardService;
    private GoldRush $goldRush;
    private BattleGlobalEventParticipationHandler $globalEventParticipationHandler;
    private PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler;
    private GoldMinesRewardHandler $goldMinesRewardHandler;
    private FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler;
    private TheOldChurchRewardHandler $theOldChurchRewardHandler;
    private WeeklyBattleService $weeklyBattleService;

    public function __construct(
        FactionHandler                        $factionHandler,
        CharacterRewardService                $characterRewardService,
        GoldRush                              $goldRush,
        BattleGlobalEventParticipationHandler $globalEventParticipationHandler,
        PurgatorySmithHouseRewardHandler      $purgatorySmithHouseRewardHandler,
        GoldMinesRewardHandler                $goldMinesRewardHandler,
        FactionLoyaltyBountyHandler           $factionLoyaltyBountyHandler,
        TheOldChurchRewardHandler             $theOldChurchRewardHandler,
        WeeklyBattleService                   $weeklyBattleService,
    ) {
        $this->factionHandler                   = $factionHandler;
        $this->characterRewardService           = $characterRewardService;
        $this->goldRush                         = $goldRush;
        $this->globalEventParticipationHandler  = $globalEventParticipationHandler;
        $this->purgatorySmithHouseRewardHandler = $purgatorySmithHouseRewardHandler;
        $this->goldMinesRewardHandler           = $goldMinesRewardHandler;
        $this->factionLoyaltyBountyHandler      = $factionLoyaltyBountyHandler;
        $this->theOldChurchRewardHandler        = $theOldChurchRewardHandler;
        $this->weeklyBattleService              = $weeklyBattleService;
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

        $this->goldRush->processPotentialGoldRush($this->character);

        $this->handleGlobalEventGoals();

        $character = $this->character->refresh();

        $character = $this->purgatorySmithHouseRewardHandler->handleFightingAtPurgatorySmithHouse($character, $this->monster);

        $character = $this->goldMinesRewardHandler->handleFightingAtGoldMines($character, $this->monster);

        $character = $this->theOldChurchRewardHandler->handleFightingAtTheOldChurch($character, $this->monster);

        $character = $this->factionLoyaltyBountyHandler->handleBounty($character, $this->monster);

        $character = $this->weeklyBattleService->handleMonsterDeath($character, $this->monster);

        BattleItemHandler::dispatch($character, $this->monster);
    }

    protected function handleFactionRewards() {
        if ($this->gameMap->mapType()->isPurgatory()) {
            return;
        }

        $this->factionHandler->handleFaction($this->character, $this->monster);

        $this->character = $this->character->refresh();
    }

    protected function handleGlobalEventGoals() {
        $event = Event::whereIn('type', [
            EventType::WINTER_EVENT,
            EventType::DELUSIONAL_MEMORIES_EVENT,
        ])->first();

        if (is_null($event)) {
            return;
        }

        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $gameMapArrays = GameMap::whereIn('name', [
            MapNameValue::ICE_PLANE,
            MapNameValue::DELUSIONAL_MEMORIES,
        ])->pluck('id')->toArray();

        if (is_null($globalEventGoal) || !in_array($this->character->map->game_map_id, $gameMapArrays)) {
            return;
        }

        $this->globalEventParticipationHandler->handleGlobalEventParticipation($this->character->refresh(), $globalEventGoal->refresh());
    }
}
