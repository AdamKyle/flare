<?php

namespace App\Game\Exploration\Handlers;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Map;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Messages\Events\ServerMessageEvent;

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

        if ($character->level >= $this->getMaxLevel($character)) {
            return;
        }

        $oldXP        = $character->xp;
        $levelsGained = round($xp / 100);


        while ($levelsGained > 0) {
            $levelsGained -= 1;

            $character->update([
                'xp' => 100
            ]);

            $character = $character->refresh();

            event(new CharacterLevelUpEvent($character, false));
        }

        $character->update([
            'xp' => $oldXP
        ]);

        $character = $character->refresh();

        event(new CharacterLevelUpEvent($character, false));

        event(new ExplorationLogUpdate($character->user, 'Gained an additional ' . $xp . ' XP.' , false, true));

        CharacterAttackTypesCacheBuilder::dispatch($character, true)->delay(now()->addSeconds(5));

        event(new ExplorationLogUpdate($character->user, 'Your character stats will update in a moment ...', false, true));
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

    private function getMaxLevel(Character $character): int {
        $item      = Item::where('effect', ItemEffectsValue::CONTNUE_LEVELING)->first();

        if (is_null($item)) {
            return MaxLevel::MAX_LEVEL;
        }

        $inventory = Inventory::where('character_id', $character->id)->first();
        $slot      = InventorySlot::where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (!is_null($slot)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }
}
