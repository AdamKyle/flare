<?php

namespace App\Game\Gambler\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\InventorySlot;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Values\EventType;
use App\Game\Gambler\Events\GamblerSlotTimeOut;
use App\Game\Gambler\Handlers\SpinHandler;
use App\Game\Gambler\Jobs\SlotTimeOut;
use App\Game\Gambler\Values\CurrencyValue;

class GamblerService
{
    use ResponseBuilder;

    /**
     * @param SpinHandler $spinHandler
     */
    public function __construct(private readonly SpinHandler $spinHandler) {}

    /**
     * Build the slot machine symbols and the character's current spin availability.
     *
     * @param Character $character
     * @return array
     */
    public function getSlotStatus(Character $character): array
    {
        return [
            'icons' => CurrencyValue::getIcons(),
            'can_spin' => ! $this->isSpinCoolingDown($character),
            'timeout_for' => $this->remainingTimeoutSeconds($character),
            'spin_cost' => $this->spinCost(),
        ];
    }

    /**
     * Spend Gold to spin the slot machine and reward any matching currency symbols.
     *
     * @param Character $character
     * @return array
     */
    public function roll(Character $character): array
    {
        if ($this->isSpinCoolingDown($character)) {
            return $this->errorResult('You must wait for the slot machine to cool down before spinning again.');
        }

        if ($character->gold < $this->spinCost()) {
            return $this->errorResult('You need '.number_format($this->spinCost()).' Gold to spin the slot machine.');
        }

        $character->update([
            'gold' => $character->gold - $this->spinCost(),
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));

        $this->spinTimeout($character);

        $rollInfo = $this->spinHandler->processRoll($this->spinHandler->roll());

        if ($rollInfo['matchingAmount'] === 2) {
            return $this->giveReward($character, $rollInfo, 1000);
        }

        if ($rollInfo['matchingAmount'] === 3) {
            return $this->giveReward($character, $rollInfo, 5000);
        }

        return $this->spinResult($character, $rollInfo, 'Darn! Better luck next time child! Spin again!', null);
    }

    /**
     * Return the Gold cost of one slot machine spin.
     *
     * @return int
     */
    private function spinCost(): int
    {
        return 1000000;
    }

    /**
     * Determine whether the character is still inside the slot machine cooldown.
     *
     * @param Character $character
     * @return bool
     */
    private function isSpinCoolingDown(Character $character): bool
    {
        if ($character->can_spin) {
            return false;
        }

        if (is_null($character->can_spin_again_at)) {
            return true;
        }

        return $character->can_spin_again_at->isFuture();
    }

    /**
     * Resolve the whole seconds remaining before the character may spin again.
     *
     * @param Character $character
     * @return int
     */
    private function remainingTimeoutSeconds(Character $character): int
    {
        if (! $this->isSpinCoolingDown($character) || is_null($character->can_spin_again_at)) {
            return 0;
        }

        return max(0, $character->can_spin_again_at->getTimestamp() - now()->getTimestamp());
    }

    /**
     * Start the slot machine cooldown for the character.
     *
     * @param Character $character
     * @return void
     */
    private function spinTimeout(Character $character): void
    {
        $time = now()->addSeconds(10);

        $character->update([
            'can_spin' => false,
            'can_spin_again_at' => $time,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new GamblerSlotTimeOut($character->user));

        SlotTimeOut::dispatch($character)->delay($time);
    }

    /**
     * Give the character the currency reward for a matching spin.
     *
     * @param Character $character
     * @param array $rollInfo
     * @param int $baseReward
     * @return array
     */
    private function giveReward(Character $character, array $rollInfo, int $baseReward): array
    {
        $attribute = (new CurrencyValue($rollInfo['matching']))->getAttribute();

        if ($attribute === 'copper_coins' && ! $this->hasCopperCoinItem($character)) {
            return $this->spinResult($character, $rollInfo, 'You do not have the quest item to get copper coins. Complete the quest: The Magic of Purgatory in Hell.', null);
        }

        $previousBalance = $character->{$attribute};
        $nominalReward = $baseReward + intdiv($baseReward * $this->resolveRewardBonusPercent($character), 100);
        $newBalance = $this->getAmount($attribute, $previousBalance + $nominalReward);

        $character->update([
            $attribute => $newBalance,
        ]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

        $creditedAmount = $newBalance - $previousBalance;

        return $this->spinResult($character, $rollInfo, $this->rewardMessage($attribute, $creditedAmount), [
            'currency' => $attribute,
            'amount' => $creditedAmount,
            'balance' => $newBalance,
        ]);
    }

    /**
     * Build the successful spin result with the character's authoritative post-spin Gold and any credited reward.
     *
     * @param Character $character
     * @param array $rollInfo
     * @param string $message
     * @param array|null $reward
     * @return array
     */
    private function spinResult(Character $character, array $rollInfo, string $message, ?array $reward): array
    {
        return $this->successResult([
            'message' => $message,
            'rolls' => $rollInfo['roll'],
            'gold' => $character->gold,
            'reward' => $reward,
        ]);
    }

    /**
     * Describe the currency actually credited by a matching spin.
     *
     * @param string $attribute
     * @param int $creditedAmount
     * @return string
     */
    private function rewardMessage(string $attribute, int $creditedAmount): string
    {
        $currencyName = ucfirst(str_replace('_', ' ', $attribute));

        if ($creditedAmount === 0) {
            return 'You matched '.$currencyName.', but you are already at the maximum amount you can hold.';
        }

        return 'You got a '.number_format($creditedAmount).' '.$currencyName.'!';
    }

    /**
     * Determine whether the character owns the quest item that unlocks Copper Coin rewards.
     *
     * @param Character $character
     * @return bool
     */
    private function hasCopperCoinItem(Character $character): bool
    {
        return $character->inventory->slots->where('item.effect', ItemEffectType::GET_COPPER_COINS->value)->isNotEmpty();
    }

    /**
     * Resolve the reward bonus percentage from events and quest items.
     *
     * @param Character $character
     * @return int
     */
    private function resolveRewardBonusPercent(Character $character): int
    {
        $totalBonusPercent = 0;

        if (Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->exists()) {
            $totalBonusPercent += 25;
        }

        $hasSlotBonusItem = $character->inventory->slots->contains(function (InventorySlot $slot) {
            return $slot->item->type === ItemCatalogType::QUEST->value && $slot->item->effect === ItemEffectType::MERCENARY_SLOT_BONUS->value;
        });

        if ($hasSlotBonusItem) {
            $totalBonusPercent += 50;
        }

        return $totalBonusPercent;
    }

    /**
     * Cap the new currency amount at the currency limit.
     *
     * @param string $attribute
     * @param int $amount
     * @return int
     */
    private function getAmount(string $attribute, int $amount): int
    {
        $limit = match ($attribute) {
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            default => null,
        };

        if (is_null($limit)) {
            return $amount;
        }

        return min($amount, $limit);
    }
}
