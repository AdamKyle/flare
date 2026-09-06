<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Events\Values\EventType;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Maps\Values\LocationType;
use App\Game\Messages\Types\CurrenciesMessageTypes;

class CharacterCurrencyRewardService
{
    private Character $character;

    private array $earnedCurrencies = [
        'gold' => 0,
        'gold_dust' => 0,
        'shards' => 0,
        'copper_coins' => 0,
    ];

    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly AreaGemEffectService $areaGemEffectService,
    ) {}

    /**
     * Set the character.
     */
    public function setCharacter(Character $character): CharacterCurrencyRewardService
    {
        $this->character = $character;
        $this->earnedCurrencies = [
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ];

        return $this;
    }

    /**
     * Give currencies.
     */
    public function giveCurrencies(Monster $monster, int $killCount = 1): array
    {

        $this->distributeGold($monster, $killCount);

        $this->distributeCopperCoins($monster, $killCount);

        $this->currencyEventReward($monster, $killCount);

        return $this->earnedCurrencies;
    }

    public function planCurrencies(Monster $monster, int $killCount = 1): array
    {
        $goldToReward = $monster->gold * $killCount;
        $copperCoins = 0;
        $eventShards = 0;
        $eventGoldDust = 0;
        $eventCopperCoins = 0;

        $copperCoinsItem = ItemModel::where('effect', ItemEffectType::GET_COPPER_COINS->value)->first();
        $mercenarySlotBonusItem = ItemModel::where('effect', ItemEffectType::MERCENARY_SLOT_BONUS->value)->first();
        $gameMap = GameMap::find($monster->game_map_id);

        if (! is_null($copperCoinsItem) && $gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $copperCoinSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $copperCoinsItem->id)->first();
            $mercenaryQuestSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $mercenarySlotBonusItem?->id)->first();

            if (! is_null($copperCoinSlot)) {
                $copperCoins = $this->randomNumberGenerator->numberBetween(5, 20) * $killCount;
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (! is_null($purgatoryDungeons)) {
                    $copperCoins *= 1.5;
                }

                if (! is_null($mercenaryQuestSlot)) {
                    $copperCoins = $copperCoins + $copperCoins * 0.5;
                }
            }
        }

        $event = ScheduledEvent::where('event_type', EventType::WEEKLY_CURRENCY_DROPS)->where('currently_running', true)->first();
        $canHaveEventCopperCoins = false;

        if (! is_null($event) && ! $monster->is_celestial_entity) {
            $canHaveEventCopperCoins = $this->character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectType::GET_COPPER_COINS->value;
            })->isNotEmpty();

            $eventShards = $this->randomNumberGenerator->numberBetween(1, 375) * $killCount;
            $eventGoldDust = $this->randomNumberGenerator->numberBetween(1, 375) * $killCount;

            if ($canHaveEventCopperCoins) {
                $eventCopperCoins = $this->randomNumberGenerator->numberBetween(1, 115) * $killCount;
            }
        }

        return [
            'kill_count' => $killCount,
            'gold' => $goldToReward,
            'copper_coins' => $copperCoins,
            'event' => [
                'active' => ! is_null($event) && ! $monster->is_celestial_entity,
                'shards' => $eventShards,
                'gold_dust' => $eventGoldDust,
                'copper_coins' => $eventCopperCoins,
                'can_have_copper_coins' => $canHaveEventCopperCoins,
            ],
        ];
    }

    public function applyPlannedCurrencies(array $plan): array
    {
        $this->earnedCurrencies = [
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ];

        $this->applyGold((int) ($plan['gold'] ?? 0));
        $this->applyCopperCoins((int) ($plan['copper_coins'] ?? 0));

        if (($plan['event']['active'] ?? false) === true) {
            $this->applyEventCurrencies($plan['event']);
        }

        return $this->earnedCurrencies;
    }

    /**
     * Handles Currency Event Rewards when the event is running.
     */
    public function currencyEventReward(Monster $monster, int $killCount = 1): CharacterCurrencyRewardService
    {

        $event = ScheduledEvent::where('event_type', EventType::WEEKLY_CURRENCY_DROPS)->where('currently_running', true)->first();

        if (! is_null($event) && ! $monster->is_celestial_entity) {

            $canHaveCopperCoins = $this->character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectType::GET_COPPER_COINS->value;
            })->isNotEmpty();

            $shards = $this->randomNumberGenerator->numberBetween(1, 375) * $killCount;

            $goldDust = $this->randomNumberGenerator->numberBetween(1, 375) * $killCount;

            $resolvedAreaGemEffects = $this->areaGemEffectService->resolveForCharacter($this->character);

            $shards = (int) round($shards * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::SHARDS_GAIN)));
            $goldDust = (int) round($goldDust * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_DUST_GAIN)));

            $this->earnedCurrencies['shards'] += $shards;
            $this->earnedCurrencies['gold_dust'] += $goldDust;

            $characterShards = $this->character->shards + $shards;
            $characterGoldDust = $this->character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = $this->randomNumberGenerator->numberBetween(1, 115) * $killCount;
                $copperCoins = (int) round($copperCoins * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN)));
                $this->earnedCurrencies['copper_coins'] += $copperCoins;

                $characterCopperCoins = $this->character->copper_coins + $copperCoins;
            } else {
                $characterCopperCoins = $this->character->copper_coins;
            }

            if ($characterShards > CurrencyLimit::MAX_SHARDS) {
                $characterShards = CurrencyLimit::MAX_SHARDS;
            }

            if ($characterCopperCoins > CurrencyLimit::MAX_COPPER) {
                $characterCopperCoins = CurrencyLimit::MAX_COPPER;
            }

            if ($characterGoldDust > CurrencyLimit::MAX_GOLD_DUST) {
                $characterGoldDust = CurrencyLimit::MAX_GOLD_DUST;
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
        $this->applyGold($goldToReward);
    }

    /**
     * Apply the Gem-adjusted gold reward to the character and report the gain.
     */
    private function applyGold(int $goldToReward): void
    {
        if ($goldToReward <= 0) {
            return;
        }

        $resolvedAreaGemEffects = $this->areaGemEffectService->resolveForCharacter($this->character);

        $goldToReward = (int) round($goldToReward * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE)));
        $goldToReward = (int) round($goldToReward * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_GAIN)));

        $this->earnedCurrencies['gold'] += $goldToReward;

        $newGold = $this->character->gold + $goldToReward;

        if ($newGold >= CurrencyLimit::MAX_GOLD) {
            $newGold = CurrencyLimit::MAX_GOLD;
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
        $copperCoinsItem = ItemModel::where('effect', ItemEffectType::GET_COPPER_COINS->value)->first();
        $mercenarySlotBonusItem = ItemModel::where('effect', ItemEffectType::MERCENARY_SLOT_BONUS->value)->first();

        if (is_null($copperCoinsItem)) {
            return;
        }

        $gameMap = GameMap::find($monster->game_map_id);

        if ($gameMap->mapType()->isPurgatory()) {
            $inventory = Inventory::where('character_id', $this->character->id)->first();
            $copperCoinSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $copperCoinsItem->id)->first();
            $mercenaryQuestSlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $mercenarySlotBonusItem->id)->first();

            if (! is_null($copperCoinSlot)) {
                $coins = $this->randomNumberGenerator->numberBetween(5, 20) * $killCount;
                $purgatoryDungeons = $this->purgatoryDungeons($this->character->map);

                if (! is_null($purgatoryDungeons)) {
                    $coins *= 1.5;
                }

                $mercenarySlotBonus = 0;

                if (! is_null($mercenaryQuestSlot)) {
                    $mercenarySlotBonus = 0.5;
                }

                $coins = $coins + $coins * $mercenarySlotBonus;

                $copperCoinGain = $this->areaGemEffectService->resolveForCharacter($this->character)->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN);
                $coins = $coins + $coins * $copperCoinGain;

                $this->earnedCurrencies['copper_coins'] += $coins;

                $newCoins = $this->character->copper_coins + $coins;

                if ($newCoins >= CurrencyLimit::MAX_COPPER) {
                    $newCoins = CurrencyLimit::MAX_COPPER;
                }

                $this->character->update(['copper_coins' => $newCoins]);

                $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::COPPER_COINS, $coins, $newCoins);
            }
        }
    }

    /**
     * Apply the Gem-adjusted copper coin reward to the character and report the gain.
     */
    private function applyCopperCoins(int $coins): void
    {
        if ($coins <= 0) {
            return;
        }

        $copperCoinGain = $this->areaGemEffectService->resolveForCharacter($this->character)->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN);
        $coins = (int) round($coins * (1 + $copperCoinGain));

        $this->earnedCurrencies['copper_coins'] += $coins;
        $newCoins = $this->character->copper_coins + $coins;

        if ($newCoins >= CurrencyLimit::MAX_COPPER) {
            $newCoins = CurrencyLimit::MAX_COPPER;
        }

        $this->character->update(['copper_coins' => $newCoins]);
        $this->character = $this->character->refresh();

        $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::COPPER_COINS, $coins, $newCoins);
    }

    /**
     * Apply the Gem-adjusted planned event currency rewards to the character and report the gains.
     */
    private function applyEventCurrencies(array $eventPlan): void
    {
        $shards = (int) ($eventPlan['shards'] ?? 0);
        $goldDust = (int) ($eventPlan['gold_dust'] ?? 0);
        $copperCoins = (int) ($eventPlan['copper_coins'] ?? 0);

        $resolvedAreaGemEffects = $this->areaGemEffectService->resolveForCharacter($this->character);

        $shards = (int) round($shards * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::SHARDS_GAIN)));
        $goldDust = (int) round($goldDust * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_DUST_GAIN)));
        $copperCoins = (int) round($copperCoins * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN)));

        $this->earnedCurrencies['shards'] += $shards;
        $this->earnedCurrencies['gold_dust'] += $goldDust;

        $characterShards = $this->character->shards + $shards;
        $characterGoldDust = $this->character->gold_dust + $goldDust;
        $characterCopperCoins = $this->character->copper_coins + $copperCoins;

        if ($characterShards > CurrencyLimit::MAX_SHARDS) {
            $characterShards = CurrencyLimit::MAX_SHARDS;
        }

        if ($characterCopperCoins > CurrencyLimit::MAX_COPPER) {
            $characterCopperCoins = CurrencyLimit::MAX_COPPER;
        }

        if ($characterGoldDust > CurrencyLimit::MAX_GOLD_DUST) {
            $characterGoldDust = CurrencyLimit::MAX_GOLD_DUST;
        }

        $this->character->update([
            'shards' => $characterShards,
            'copper_coins' => $characterCopperCoins,
            'gold_dust' => $characterGoldDust,
        ]);

        $this->character = $this->character->refresh();

        $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::GOLD_DUST, $goldDust, $characterGoldDust);
        $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::SHARDS, $shards, $characterShards);

        if (($eventPlan['can_have_copper_coins'] ?? false) && $copperCoins > 0) {
            $this->earnedCurrencies['copper_coins'] += $copperCoins;
            $this->battleMessageHandler->handleCurrencyGainMessage($this->character->user, CurrenciesMessageTypes::COPPER_COINS, $copperCoins, $characterCopperCoins);
        }

    }

    /**
     * Are we at a location with an effect (special location)?
     */
    private function purgatoryDungeons(Map $map): ?Location
    {
        return Location::where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS->value)
            ->first();
    }
}
