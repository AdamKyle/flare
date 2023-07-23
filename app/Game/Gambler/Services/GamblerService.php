<?php

namespace App\Game\Gambler\Services;

use App\Flare\Models\Character;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gambler\Events\GamblerSlotTimeOut;
use App\Game\Gambler\Handlers\SpinHandler;
use App\Game\Gambler\Jobs\SlotTimeOut;
use App\Game\Gambler\Values\CurrencyValue;
use Exception;

class GamblerService {

    use ResponseBuilder, MercenaryBonus;

    private SpinHandler $spinHandler;

    public function __construct(SpinHandler $spinHandler) {
        $this->spinHandler = $spinHandler;
    }

    /**
     * Spin the wheel.
     *
     * @param Character $character
     * @return array
     * @throws Exception
     */
    public function roll(Character $character): array {

        if ($character->gold < 1000000) {
            return $this->errorResult('Not enough gold');
        }

        $character->update([
            'gold' => $character->gold - 1000000
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));

        $this->spinTimeout($character);

        $rollInfo = $this->spinHandler->roll();

        $rollInfo = $this->spinHandler->processRoll($rollInfo);

        if ($rollInfo['matchingAmount'] === 2) {
            return $this->giveReward($character, $rollInfo, 250);
        }

        if ($rollInfo['matchingAmount'] === 3) {
            return $this->giveReward($character, $rollInfo, 500);
        }

        return $this->successResult([
            'message' => 'Darn! Better luck next time child! Spin again!',
            'rolls'   => $rollInfo['roll'],
        ]);
    }

    /**
     * handle the spin timeout.
     *
     * @param Character $character
     * @return void
     */
    protected function spinTimeout(Character $character): void {

        $time = now()->addSeconds(10);

        $character->update([
            'can_spin'            => false,
            'can_spin_again_at'   => $time,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new GamblerSlotTimeOut($character->user));

        SlotTimeOut::dispatch($character)->delay($time);
    }

    /**
     * Give reward for matching.
     *
     * @param Character $character
     * @param array $rollInfo
     * @param int $amountToWin
     * @return array
     * @throws Exception
     */
    protected function giveReward(Character $character, array $rollInfo, int $amountToWin): array {
        $attribute = (new CurrencyValue($rollInfo['matching']))->getAttribute();

        if ($attribute === 'copper_coins') {
            $hasItem = $character->inventory->slots->where('item.effect', ItemEffectsValue::GET_COPPER_COINS)->isNotEmpty();

            if (!$hasItem) {
                return $this->successResult([
                    'message' => 'Your do not have the quest item to get copper coins. Complete the quest: The Magic of Purgatory in Hell.',
                    'rolls'   => $rollInfo['roll'],
                ]);
            }
        }

        $newAmount = $character->{$attribute} + $amountToWin;

        if ($attribute === 'shards') {

            $amountToWin = $amountToWin + $amountToWin * $this->getShardBonus($character);
            $amountToWin = $amountToWin + $amountToWin * $this->getGamblerBonus($character);

            $newAmount   = $character->{$attribute} + $amountToWin;

            $newAmount = $newAmount;
        }

        if ($attribute === 'gold_dust') {

            $amountToWin = $amountToWin + $amountToWin * $this->getGoldDustBonus($character);
            $amountToWin = $amountToWin + $amountToWin * $this->getGamblerBonus($character);

            $newAmount   = $character->{$attribute} + $amountToWin;

            $newAmount = $newAmount;
        }

        if ($attribute === 'copper_coins') {

            $amountToWin = $amountToWin + $amountToWin * $this->getCopperCoinBonus($character);
            $amountToWin = $amountToWin + $amountToWin * $this->getGamblerBonus($character);

            $newAmount   = $character->{$attribute} + $amountToWin;

            $newAmount = $newAmount;
        }

        $newAmount = $this->getAmount($attribute, $newAmount);

        $character->{$attribute} = $newAmount;
        $character->save();

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

        return $this->successResult([
            'message' => 'You got a ' . number_format($amountToWin) . ' ' . ucfirst(str_replace('_', ' ', $attribute)) . '!',
            'rolls'   => $rollInfo['roll'],
        ]);
    }

    /**
     * Get new amount of currency for player.
     *
     * @param string $attribute
     * @param int $amount
     * @return int
     */
    protected function getAmount(string $attribute, int $amount): int {

        if ($attribute === 'gold_dust') {
            if ($amount > MaxCurrenciesValue::MAX_GOLD_DUST) {
                return MaxCurrenciesValue::MAX_GOLD_DUST;
            }
        }

        if ($attribute === 'shards') {
            if ($amount > MaxCurrenciesValue::MAX_SHARDS) {
                return MaxCurrenciesValue::MAX_SHARDS;
            }
        }

        if ($attribute === 'copper_coins') {
            if ($amount > MaxCurrenciesValue::MAX_COPPER) {
                return MaxCurrenciesValue::MAX_COPPER;
            }
        }

        return $amount;
    }
}
