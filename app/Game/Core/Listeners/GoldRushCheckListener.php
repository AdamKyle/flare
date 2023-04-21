<?php

namespace App\Game\Core\Listeners;

use Exception;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;

class GoldRushCheckListener {

    /**
     * @param GoldRushCheckEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(GoldRushCheckEvent $event): void {

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

            ServerMessageHandler::handleMessage($character->user, $type, number_format($goldRush));

            event(new UpdateTopBarEvent($character));
        }
    }
}
