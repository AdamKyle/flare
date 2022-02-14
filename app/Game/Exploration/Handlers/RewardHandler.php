<?php

namespace App\Game\Exploration\Handlers;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Exploration\Events\ExplorationLogUpdate;

class RewardHandler {

    private $factionHandler;

    public function __construct(FactionHandler $factionHandler) {
        $this->factionHandler = $factionHandler;
    }

    public function processRewardsForEncounter(Character $character) {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        $this->processEncounterXPBonus($character, 200);

        $this->processEncounterFactionBonus($character, 5);

        if (!$gameMap->mapType()->isPurgatory()) {
            event(new ExplorationLogUpdate($character->user, 'Gained an additional 5 Faction points (50 if you have the Faction quest item from the Helpless Goblin AND are above level 0. If you are maxed you gained nothing.).', false, true));
        }

        $this->processEncounterGoldBonus($character, 10000);

        event(new ExplorationLogUpdate($character->user, 'Gained an additional 10K gold (unless you are Gold capped).', false, true));
    }

    public function processRewardsForExplorationComplete(Character $character) {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        $this->processEncounterXPBonus($character, 1000);

        $this->processEncounterFactionBonus($character, 100);

        if (!$gameMap->mapType()->isPurgatory()) {
            event(new ExplorationLogUpdate($character->user, 'Gained an additional 100 Faction points (1000 if you have the Faction quest item from the Helpless Goblin AND are above level 0. If you are maxed you gained nothing.).', false, true));
        }

        $this->processEncounterGoldBonus($character, 100000);

        event(new ExplorationLogUpdate($character->user, 'Gained an additional 10K gold (unless you are Gold capped).', false, true));
    }

    protected function processEncounterXPBonus(Character $character, int $xp) {

        if ($character->level >= $character->max_level) {
            return;
        }

        $oldXP        = $character->xp;
        $levelsGained = round(100 / $xp);

        while ($levelsGained > 0) {
            $levelsGained -= 1;

            $character->update([
                'xp' => 100
            ]);

            $character = $character->refresh();

            event(new CharacterLevelUpEvent($character));
        }

        $character->update([
            'xp' => $oldXP
        ]);

        $character = $character->refresh();

        event(new CharacterLevelUpEvent($character));

        event(new ExplorationLogUpdate($character->user, 'Gained an additional 200XP.', false, true));
    }

    protected function processEncounterFactionBonus(Character $character, int $amount) {
        $this->factionHandler->handleCustomFactionAmount($character, $amount);
    }

    protected function processEncounterGoldBonus(Character $character, int $amount) {
        $newGold = $character->gold + $amount;

        $maxCurrencies = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }
}
