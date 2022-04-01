<?php

namespace App\Flare\Services;

use Cache;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\GlobalMessageEvent;

class DailyGoldDustService {

    /**
     * 999,999 (1% chance)
     *
     * @var int $lottoChance
     */
    private $lottoChance = 999999;

    /**
     * Lotto max that a player can win.
     *
     * @var int $lottoMax
     */
    private $lottoMax    = 1000000;

    public function handleDailyLottery(Character $character) {

        if ($this->rollForLottery() > $this->lottoChance) {
            $this->handleWonDailyLottery($character);
        } else {
            $this->handleRegularDailyGoldDust($character);
        }
    }

    protected function rollForLottery(): int {
        return rand(1, 1000000);
    }

    protected function handleRegularDailyGoldDust(Character $character) {
        $amount = rand(1,100);

        $newAmount = $character->gold_dust + $this->lottoMax;

        if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newAmount,
        ]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'daily_lottery', $amount));

        event(new UpdateTopBarEvent($character));
    }

    protected function handleWonDailyLottery(Character $character) {
        event(new GlobalMessageEvent($character->name . 'has won the daily Gold Dust Lottery!'));

        $newAmount = $character->gold_dust + $this->lottoMax;

        if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newAmount,
        ]);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'lotto_max', $this->lottoMax));

        event(new UpdateTopBarEvent($character));

        Cache::put('won-lotto', now());
    }

}
