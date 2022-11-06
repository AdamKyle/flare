<?php

namespace App\Game\Gambler\Services;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gambler\Events\GamblerSlotTimeOut;
use App\Game\Gambler\Jobs\SlotTimeOut;
use App\Game\Gambler\Values\CurrencyValue;

class GamblerService {

    use ResponseBuilder;

    public function roll(Character $character): array {
        $this->spinTimeout($character);

        $rollInfo = CurrencyValue::roll();

        if ($rollInfo['matchingAmount'] === 2) {
            return $this->giveReward($character, $rollInfo, 100);
        }

        if ($rollInfo['matchingAmount'] === 3) {
            return $this->giveReward($character, $rollInfo, 500);
        }

        return $this->successResult([
            'message' => 'Darn! Better luck next time child! Spin again!',
            'rolls'   => $rollInfo['roll'],
        ]);
    }

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

    protected function giveReward(Character $character, array $rollInfo, int $amountToWin): array {
        $attribute = (new CurrencyValue($rollInfo['matching']))->getAttribute();

        $newAmount = $character->{$attribute} + $amountToWin;
        $newAmount = $this->getAmount($attribute, $newAmount);

        $character->{$attribute} = $newAmount;
        $character->save();

        event(new UpdateTopBarEvent($character->refresh()));

        return $this->successResult([
            'message' => 'You got a ' . $amountToWin . ' ' . ucfirst(str_replace('_', ' ', $attribute)) . '!',
            'rolls'   => $rollInfo['roll'],
        ]);
    }

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
