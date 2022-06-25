<?php

namespace App\Game\Core\Listeners;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
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

        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $gameMapBonus);

        if ($hasGoldRush) {
            $goldRush = ceil($event->character->gold + $event->character->gold * 0.03);

            $maxCurrencies = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

            $type = 'gold_rush';

            if ($maxCurrencies->canNotGiveCurrency()) {
                $event->character->gold = MaxCurrenciesValue::MAX_GOLD;;
                $event->character->save();

                $type = 'gold_capped';
            } else {
                $event->character->gold = $goldRush;
                $event->character->save();
            }

            $character = $event->character->refresh();

            event(new ServerMessageEvent($character->user, $type, number_format($goldRush)));
            event(new UpdateTopBarEvent($character));
        }
    }
}
