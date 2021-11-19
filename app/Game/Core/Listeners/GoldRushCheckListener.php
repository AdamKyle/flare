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
        $gameMap        = $event->character->map->gameMap;
        $gameMapBonus   = 0.0;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $gameMapBonus, $event->adventure);

        if ($hasGoldRush) {
            $goldRush = ceil($event->character->gold + $event->character->gold * 0.03);

            $maxCurrentices = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

            if ($maxCurrentices->canNotGiveCurrency()) {
                event(new UpdateTopBarEvent($event->character->refresh()));

                return;
            }

            $event->character->gold = $goldRush;
            $event->character->save();

            $character = $event->character->refresh();

            event(new ServerMessageEvent($character->user, 'gold_rush', ceil($character->gold * 0.03)));
            event(new UpdateTopBarEvent($character));
        }
    }
}
