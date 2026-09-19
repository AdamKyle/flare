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
use App\Game\Gems\Progression\Contracts\CharacterAreaGemEffects;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Maps\Values\LocationType;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use RuntimeException;
use Throwable;

class CharacterCurrencyRewardService
{
    private Character $character;

    private array $earnedCurrencies = [
        'gold' => 0,
        'gold_dust' => 0,
        'shards' => 0,
        'copper_coins' => 0,
    ];

    private ?Throwable $currencyCalculationFailure = null;

    /**
     * @param BattleMessageHandler $battleMessageHandler
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param CharacterAreaGemEffects $characterAreaGemEffects
     */
    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly CharacterAreaGemEffects $characterAreaGemEffects,
    ) {}

    /**
     * Set the character.
     *
     * @param Character $character
     * @return CharacterCurrencyRewardService
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
        $this->currencyCalculationFailure = null;

        return $this;
    }

    /**
     * Return the invalid whole-currency calculation failure recorded during the most recent
     * currency operation, if one occurred, so the caller can fail the owning reward operation
     * instead of treating a corrupted calculation as a silent or extreme currency reward.
     *
     * @return ?Throwable
     */
    public function currencyCalculationFailure(): ?Throwable
    {
        return $this->currencyCalculationFailure;
    }

    /**
     * Give currencies.
     *
     * @param Monster $monster
     * @param int $killCount
     * @return array
     */
    public function giveCurrencies(Monster $monster, int $killCount = 1): array
    {

        $this->distributeGold($monster, $killCount);

        $this->distributeCopperCoins($monster, $killCount);

        $this->currencyEventReward($monster, $killCount);

        return $this->earnedCurrencies;
    }

    /**
     * Plan the currency rewards for the Monster without applying them.
     *
     * @param Monster $monster
     * @param int $killCount
     * @return array
     */
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

    /**
     * Apply already-calculated Gem Scroll currency bonus amounts on top of already-earned base currencies.
     *
     * @param Character $character
     * @param array $bonusAmounts
     * @return array
     */
    public function applyGemScrollBonus(Character $character, array $bonusAmounts): array
    {
        return [
            'gold' => $this->applyCappedBonus($character, 'gold', $bonusAmounts['gold'] ?? 0, CurrencyLimit::MAX_GOLD, CurrenciesMessageTypes::GOLD),
            'gold_dust' => $this->applyCappedBonus($character, 'gold_dust', $bonusAmounts['gold_dust'] ?? 0, CurrencyLimit::MAX_GOLD_DUST, CurrenciesMessageTypes::GOLD_DUST),
            'shards' => $this->applyCappedBonus($character, 'shards', $bonusAmounts['shards'] ?? 0, CurrencyLimit::MAX_SHARDS, CurrenciesMessageTypes::SHARDS),
            'copper_coins' => $this->applyCappedBonus($character, 'copper_coins', $bonusAmounts['copper_coins'] ?? 0, CurrencyLimit::MAX_COPPER, CurrenciesMessageTypes::COPPER_COINS),
        ];
    }

    /**
     * Apply one currency bonus amount to the given Character column, capped at the given limit.
     *
     * @param Character $character
     * @param string $column
     * @param int $bonusAmount
     * @param int $limit
     * @param CurrenciesMessageTypes $messageType
     * @return array
     */
    private function applyCappedBonus(Character $character, string $column, int $bonusAmount, int $limit, CurrenciesMessageTypes $messageType): array
    {
        if ($bonusAmount <= 0) {
            return ['granted' => 0, 'wasted' => 0];
        }

        $currentAmount = $character->{$column};
        $uncappedNewAmount = $currentAmount + $bonusAmount;
        $newAmount = min($uncappedNewAmount, $limit);
        $granted = $newAmount - $currentAmount;
        $wasted = $uncappedNewAmount - $newAmount;

        if ($granted <= 0) {
            return ['granted' => 0, 'wasted' => $bonusAmount];
        }

        $character->update([$column => $newAmount]);

        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, $messageType, $granted, $newAmount);

        return ['granted' => $granted, 'wasted' => $wasted];
    }

    /**
     * Apply an already-planned currency reward to the Character.
     *
     * @param array $plan
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return array
     */
    public function applyPlannedCurrencies(array $plan, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): array
    {
        $this->earnedCurrencies = [
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ];
        $this->currencyCalculationFailure = null;

        $resolvedAreaGemEffects ??= $this->characterAreaGemEffects->resolveForCharacterId($this->character->id);

        $this->applyGold($plan['gold'] ?? 0, $resolvedAreaGemEffects);

        $copperCoins = $this->truncateToWholeCurrency($plan['copper_coins'] ?? 0);

        if (! is_null($copperCoins)) {
            $this->applyCopperCoins($copperCoins, $resolvedAreaGemEffects);
        }

        if (($plan['event']['active'] ?? false) === true) {
            $this->applyEventCurrencies($plan['event'], $resolvedAreaGemEffects);
        }

        return $this->earnedCurrencies;
    }

    /**
     * Handles Currency Event Rewards when the event is running.
     *
     * @param Monster $monster
     * @param int $killCount
     * @return CharacterCurrencyRewardService
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

            $resolvedAreaGemEffects = $this->characterAreaGemEffects->resolveForCharacterId($this->character->id);

            $shards = $this->roundToWholeCurrency($shards * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::SHARDS_GAIN)));
            $goldDust = $this->roundToWholeCurrency($goldDust * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_DUST_GAIN)));

            if (is_null($shards) || is_null($goldDust)) {
                return $this;
            }

            $this->earnedCurrencies['shards'] += $shards;
            $this->earnedCurrencies['gold_dust'] += $goldDust;

            $characterShards = $this->character->shards + $shards;
            $characterGoldDust = $this->character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = $this->randomNumberGenerator->numberBetween(1, 115) * $killCount;
                $copperCoins = $this->roundToWholeCurrency($copperCoins * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN)));

                if (is_null($copperCoins)) {
                    return $this;
                }

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
     *
     * @return Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Gives gold to the player.
     *
     * @param Monster $monster
     * @param int $killCount
     * @return void
     */
    private function distributeGold(Monster $monster, int $killCount): void
    {
        $goldToReward = $monster->gold * $killCount;
        $this->applyGold($goldToReward);
    }

    /**
     * Apply the Gem-adjusted gold reward to the character and report the gain.
     *
     * @param int $goldToReward
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return void
     */
    private function applyGold(int $goldToReward, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): void
    {
        if ($goldToReward <= 0) {
            return;
        }

        $resolvedAreaGemEffects ??= $this->characterAreaGemEffects->resolveForCharacterId($this->character->id);

        $goldToReward = $this->roundToWholeCurrency($goldToReward * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE)));

        if (is_null($goldToReward)) {
            return;
        }

        $goldToReward = $this->roundToWholeCurrency($goldToReward * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_GAIN)));

        if (is_null($goldToReward)) {
            return;
        }

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
     *
     * @param Monster $monster
     * @param int $killCount
     * @return void
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

                $copperCoinGain = $this->characterAreaGemEffects->resolveForCharacterId($this->character->id)->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN);
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
     *
     * @param int $coins
     * @param ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return void
     */
    private function applyCopperCoins(int $coins, ResolvedAreaGemEffects $resolvedAreaGemEffects): void
    {
        if ($coins <= 0) {
            return;
        }

        $copperCoinGain = $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN);
        $coins = $this->roundToWholeCurrency($coins * (1 + $copperCoinGain));

        if (is_null($coins)) {
            return;
        }

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
     *
     * @param array $eventPlan
     * @param ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return void
     */
    private function applyEventCurrencies(array $eventPlan, ResolvedAreaGemEffects $resolvedAreaGemEffects): void
    {
        $shards = $eventPlan['shards'] ?? 0;
        $goldDust = $eventPlan['gold_dust'] ?? 0;
        $copperCoins = $eventPlan['copper_coins'] ?? 0;

        $shards = $this->roundToWholeCurrency($shards * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::SHARDS_GAIN)));
        $goldDust = $this->roundToWholeCurrency($goldDust * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::GOLD_DUST_GAIN)));
        $copperCoins = $this->roundToWholeCurrency($copperCoins * (1 + $resolvedAreaGemEffects->rewardEffect(AreaGemRewardEffect::COPPER_COIN_GAIN)));

        if (is_null($shards) || is_null($goldDust) || is_null($copperCoins)) {
            return;
        }

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
     *
     * @param Map $map
     * @return ?Location
     */
    private function purgatoryDungeons(Map $map): ?Location
    {
        return Location::where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->where('type', LocationType::PURGATORY_DUNGEONS->value)
            ->first();
    }

    /**
     * Round a Gem-adjusted floating currency calculation to its nearest whole currency unit, or
     * null when the calculation is invalid.
     *
     * @param float $amount
     * @return ?int
     */
    private function roundToWholeCurrency(float $amount): ?int
    {
        return $this->wholeCurrencyAmount(round($amount));
    }

    /**
     * Truncate a floating planned currency amount down to a whole currency unit, or null when the
     * calculation is invalid.
     *
     * @param float $amount
     * @return ?int
     */
    private function truncateToWholeCurrency(float $amount): ?int
    {
        return $this->wholeCurrencyAmount(floor($amount));
    }

    /**
     * Validate and extract the genuine integer value of an already-whole floating currency amount,
     * or null when the calculation is invalid. The game's currency domain never exceeds the platform
     * integer range, so a failed validation means the calculation that produced this amount is
     * corrupted; that failure is recorded on `currencyCalculationFailure()` instead of silently
     * applying a zero, `PHP_INT_MAX`, or `PHP_INT_MIN` currency reward, so the caller can fail the
     * owning reward operation.
     *
     * @param float $amount
     * @return ?int
     */
    private function wholeCurrencyAmount(float $amount): ?int
    {
        $wholeAmount = filter_var($amount, FILTER_VALIDATE_INT);

        if ($wholeAmount === false) {
            $this->currencyCalculationFailure = new RuntimeException(
                'Invalid whole currency amount calculated: '.$amount.' cannot be represented as an integer.',
            );

            return null;
        }

        return $wholeAmount;
    }
}
