<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyCacheType;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Contracts\CharacterGoldBarDeposit;

class CharacterCurrencyCacheService
{
    use ResponseBuilder;

    /**
     * @param CharacterGoldBarDeposit $characterGoldBarDeposit
     */
    public function __construct(
        private readonly CharacterGoldBarDeposit $characterGoldBarDeposit,
    ) {}

    /**
     * Withdraw as much currency as the Character can hold from an owned Compensation Cache.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return array
     */
    public function useCache(Character $character, AlchemyBagSlot $slot): array
    {
        $validationError = $this->validateCacheSlot($character, $slot);

        if (! is_null($validationError)) {
            return $validationError;
        }

        $item = $slot->item;
        $type = $item->currency_cache_type;

        $withdrawnAmount = $this->depositIntoCharacter($character, $type, $item->cache_amount);

        if ($withdrawnAmount === 0) {
            return $this->errorResult($this->capacityErrorMessage($type));
        }

        $remainingAmount = $this->reduceCache($slot, $item, $withdrawnAmount);

        $this->broadcastUpdates($character);

        return $this->successResult([
            'message' => $this->withdrawnMessage($type, $withdrawnAmount, $remainingAmount),
            'withdrawn_amount' => $withdrawnAmount,
            'remaining_cache_amount' => $remainingAmount,
        ]);
    }

    /**
     * Validate that the slot is an owned, usable Compensation Cache with currency remaining.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return ?array
     */
    private function validateCacheSlot(Character $character, AlchemyBagSlot $slot): ?array
    {
        $alchemyBag = $character->alchemyBag;

        if (is_null($alchemyBag) || $slot->alchemy_bag_id !== $alchemyBag->id || $slot->character_id !== $character->id || $slot->amount <= 0) {
            return $this->errorResult('No. Not yours!');
        }

        $item = $slot->item;

        if (is_null($item) || is_null($item->currency_cache_type)) {
            return $this->errorResult('That Alchemy Item is not a Compensation Cache.');
        }

        if (is_null($item->cache_amount) || $item->cache_amount <= 0) {
            return $this->errorResult('This Compensation Cache has no currency left in it.');
        }

        if (! $item->usable) {
            return $this->errorResult('This Compensation Cache cannot be used right now.');
        }

        return null;
    }

    /**
     * Deposit up to the cache amount into the Character's currency or, through the Kingdoms module, their Kingdoms.
     *
     * @param Character $character
     * @param CurrencyCacheType $type
     * @param int $cacheAmount
     * @return int
     */
    private function depositIntoCharacter(Character $character, CurrencyCacheType $type, int $cacheAmount): int
    {
        if ($type === CurrencyCacheType::GOLD_BARS) {
            return $this->characterGoldBarDeposit->deposit(
                $character->id,
                $cacheAmount,
            );
        }

        return $this->depositCurrency($character, $type, $cacheAmount);
    }

    /**
     * Add up to the cache amount to the Character's currency without exceeding its cap.
     *
     * @param Character $character
     * @param CurrencyCacheType $type
     * @param int $cacheAmount
     * @return int
     */
    private function depositCurrency(Character $character, CurrencyCacheType $type, int $cacheAmount): int
    {
        [$attribute, $cap] = match ($type) {
            CurrencyCacheType::GOLD => ['gold', CurrencyLimit::MAX_GOLD],
            CurrencyCacheType::GOLD_DUST => ['gold_dust', CurrencyLimit::MAX_GOLD_DUST],
            CurrencyCacheType::SHARDS => ['shards', CurrencyLimit::MAX_SHARDS],
            CurrencyCacheType::COPPER_COINS => ['copper_coins', CurrencyLimit::MAX_COPPER],
        };

        $withdrawnAmount = min($cacheAmount, max(0, $cap - $character->{$attribute}));

        if ($withdrawnAmount === 0) {
            return 0;
        }

        $character->update([$attribute => $character->{$attribute} + $withdrawnAmount]);

        return $withdrawnAmount;
    }

    /**
     * Reduce the cache by the withdrawn amount, hard deleting the slot and Item once it is empty.
     *
     * @param AlchemyBagSlot $slot
     * @param Item $item
     * @param int $withdrawnAmount
     * @return int
     */
    private function reduceCache(AlchemyBagSlot $slot, Item $item, int $withdrawnAmount): int
    {
        $remainingAmount = $item->cache_amount - $withdrawnAmount;

        if ($remainingAmount > 0) {
            $item->update(['cache_amount' => $remainingAmount]);

            return $remainingAmount;
        }

        $slot->delete();
        $item->delete();

        return 0;
    }

    /**
     * Broadcast the Character's refreshed inventory count and currencies.
     *
     * @param Character $character
     * @return void
     */
    private function broadcastUpdates(Character $character): void
    {
        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));
        event(new UpdateTopBarEvent($character));
    }

    /**
     * Build the message explaining why nothing could be withdrawn from the cache.
     *
     * @param CurrencyCacheType $type
     * @return string
     */
    private function capacityErrorMessage(CurrencyCacheType $type): string
    {
        if ($type === CurrencyCacheType::GOLD_BARS) {
            return 'None of your kingdoms have room for more Gold Bars (each kingdom holds at most 1,000). Nothing was withdrawn from this cache.';
        }

        return 'You are already holding the maximum amount of '.$type->label().'. Nothing was withdrawn from this cache.';
    }

    /**
     * Build the message describing a successful withdrawal.
     *
     * @param CurrencyCacheType $type
     * @param int $withdrawnAmount
     * @param int $remainingAmount
     * @return string
     */
    private function withdrawnMessage(CurrencyCacheType $type, int $withdrawnAmount, int $remainingAmount): string
    {
        $withdrawn = 'Withdrew '.number_format($withdrawnAmount).' '.$type->label().'.';

        if ($remainingAmount === 0) {
            return $withdrawn.' The cache is now empty and has been removed.';
        }

        return $withdrawn.' '.number_format($remainingAmount).' '.$type->label().' remain in this cache.';
    }
}
