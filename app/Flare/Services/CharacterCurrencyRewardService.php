<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Types\CurrenciesMessageTypes;

class CharacterCurrencyRewardService
{
    private Character $character;

    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
    ) {}

    /**
     * Set the character.
     */
    public function setCharacter(Character $character): CharacterCurrencyRewardService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Give currencies.
     */
    public function giveCurrencies(Monster $monster, int $killCount = 1): CharacterCurrencyRewardService
    {

        $this->distributeGold($monster, $killCount);

        $this->distributeCopperCoins($monster, $killCount);

        $this->currencyEventReward($monster, $killCount);

        if ($this->character->isLoggedIn()) {
            event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
        }

        return $this;
    }

    /**
     * Handles Currency Event Rewards when the event is running.
     */
    public function currencyEventReward(Monster $monster, int $killCount = 1): CharacterCurrencyRewardService
    {

        $event = ScheduledEvent::where('event_type', EventType::WEEKLY_CURRENCY_DROPS)->where('currently_running', true)->first();

        if (! is_null($event) && ! $monster->is_celestial_entity) {

            $canHaveCopperCoins = $this->character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::GET_COPPER_COINS;
            })->isNotEmpty();

            $shards = rand(1, 500) * $killCount;

            $goldDust = rand(1, 500) * $killCount;

            $characterShards = $this->character->shards + $shards;
            $characterGoldDust = $this->character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = rand(1, 150) * $killCount;

                $characterCopperCoins = $this->character->copper_coins + $copperCoins;
            } else {
                $characterCopperCoins = $this->character->copper_coins;
            }

            if ($characterShards > MaxCurrenciesValue::MAX_SHARDS) {
                $characterShards = MaxCurrenciesValue::MAX_SHARDS;
            }

            if ($characterCopperCoins > MaxCurrenciesValue::MAX_COPPER) {
                $characterCopperCoins = MaxCurrenciesValue::MAX_COPPER;
            }

            if ($characterGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
                $characterGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $this->character->update([
                'shards' => $characterShards,
                'copper_coins' => $characterCopperCoins,
                'gold_dust' => $characterGoldDust,
            ]);

            $this->character = $this->character->refresh();

            $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::GOLD_DUST, $goldDust, $characterGoldDust);
            $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::SHARDS, $shards, $characterShards);

            if ($canHaveCopperCoins) {
                $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::COPPER_COINS, $copperCoins, $characterCopperCoins);
            }

            if (! $this->character->is_auto_battling) {
                event(new UpdateCharacterCurrenciesEvent($this->character->refresh()));
            }
        }

        return $this;
    }

    /**
     * Gets the character.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Gives gold to the player.
     */
    private function distributeGold(Monster $monster, int $killCount): void
    {
        $goldToReward = $monster->gold * $killCount;

        $newGold = $this->character->gold + $goldToReward;

        if ($newGold >= MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $this->character->update([
            'gold' => $newGold,
        ]);

        $character = $this->character->refresh();

        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD, $goldToReward, $newGold);
    }

    /**
     * Give copper coins only to those that have the quest item and are on purgatory.
     */
    private function distributeCopperCoins(Monster $monster, int $killCount): void
    {
        $copperCoinsItem = ItemModel::where('effect', ItemEffectsValue::GET_COPPER_COINS)->first();
        $mercenarySlotBonusItem = ItemModel::where('effect', ItemEffectsValue::MERCENARY_SLOT_BONUS)->first();

        if (is_null($copperCoinsItem)) {
            return;
        }

        $gameMap = GameMap::find($monster->game_map_id);

        if ($gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $copperCoinSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $copperCoinsItem->id)->first();
            $mercenaryQuestSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $mercenarySlotBonusItem->id)->first();

            if (! is_null($copperCoinSlot)) {
                $coins = rand(5, 20) * $killCount;
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (! is_null($purgatoryDungeons)) {
                    $coins *= 1.5;
                }

                $mercenarySlotBonus = 0;

                if (! is_null($mercenaryQuestSlot)) {
                    $mercenarySlotBonus = 0.5;
                }

                $coins = $coins + $coins * $mercenarySlotBonus;

                $newCoins = $this->character->copper_coins + $coins;

                if ($newCoins >= MaxCurrenciesValue::COPPER) {
                    $newCoins = MaxCurrenciesValue::MAX_COPPER;
                }

                $this->character->update(['copper_coins' => $newCoins]);

                $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::COPPER_COINS, $coins, $newCoins);
            }
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     */
    private function purgatoryDungeons(Map $map): ?Location
    {
        return Location::whereNotNull('enemy_strength_type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS)
            ->first();
    }
}
