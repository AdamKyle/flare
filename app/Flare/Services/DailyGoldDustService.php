<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Types\LotteryMessageType;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class DailyGoldDustService
{
    const LOTTO_MAX = 10000;

    /**
     * Handle regular amounts of gold dust.
     */
    public function handleRegularDailyGoldDust(Character $character): void
    {
        $amount = rand(1, 100);

        $newAmount = $character->gold_dust + $amount;

        if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $newAmount,
        ]);

        $character = $character->refresh();

        ServerMessageHandler::handleMessageWithNewValue($character->user, LotteryMessageType::DAILY_LOTTERY, number_format($amount), number_format($character->gold_dust));

        event(new UpdateTopBarEvent($character));
    }

    /**
     * Handle the winner  of daily gold dust.
     */
    public function handleWonDailyLottery(Character $character): void
    {

        if (! Cache::has('daily-gold-dust-lottery-won')) {
            event(new GlobalMessageEvent(
                $character->name . ' has won the daily Gold Dust Lottery!
            (Gold Dust is used in Alchemy and Quests - See Help section -> Click Help I\'m stuck, and see currencies under: Character Information -> Currencies)'
            ));

            $newAmount = $character->gold_dust + self::LOTTO_MAX;

            if ($newAmount >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                $newAmount = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $character->update([
                'gold_dust' => $newAmount,
            ]);

            $character = $character->refresh();

            Cache::put('daily-gold-dust-lottery-won', $character->id);
        }

        ServerMessageHandler::handleMessageWithNewValue($character->user, LotteryMessageType::LOTTO_MAX, number_format(self::LOTTO_MAX), number_format($character->gold_dust));

        event(new UpdateTopBarEvent($character));
    }
}
