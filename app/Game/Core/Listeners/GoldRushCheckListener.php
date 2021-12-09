<?php

namespace App\Game\Core\Listeners;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;

class GoldRushCheckListener
{

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(GoldRushCheckEvent $event)
    {

        if ($event->character->gold === MaxCurrenciesValue::MAX_GOLD) {
            return; // They are at max, cannot receive anymore.
        }

        $gameMap        = $event->character->map->gameMap;
        $gameMapBonus   = 0.0;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $gameMapBonus, $event->adventure);

        if ($hasGoldRush) {
            $goldRush = ceil($event->character->gold + $event->character->gold * 0.03);

            $maxCurrentices = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

            $subtractedAmount = 0;

            if ($maxCurrentices->canNotGiveCurrency()) {
                $subtractedAmount = $goldRush - MaxCurrenciesValue::MAX_GOLD;
                $goldRush         = $goldRush - $subtractedAmount;

                $event->character->gold = $goldRush;
                $event->charactr->save();
            } else {
                $event->character->gold = $goldRush;
                $event->character->save();
            }

            $character = $event->character->refresh();

            $type = 'gold_rush';

            if ($subtractedAmount !== 0) {
                $type = 'gold_capped';
            }

            event(new ServerMessageEvent($character->user, $type, number_format($goldRush)));
            event(new UpdateTopBarEvent($character));
        }
    }
}
