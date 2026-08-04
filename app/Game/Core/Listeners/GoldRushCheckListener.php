<?php

namespace App\Game\Core\Listeners;

use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyType;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Exception;
use Facades\App\Game\Core\Chance\GoldRushCheckCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GoldRushCheckListener
{
    /**
     * @throws Exception
     */
    public function handle(GoldRushCheckEvent $event): void
    {

        if ($event->character->gold === CurrencyLimit::MAX_GOLD) {
            return; // They are at max, cannot receive anymore.
        }

        $gameMap = $event->character->map->gameMap;
        $gameMapBonus = 0.0;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $hasGoldRush = GoldRushCheckCalculator::fetchGoldRushChance($gameMapBonus);

        if ($hasGoldRush) {
            $goldRush = ceil($event->character->gold + $event->character->gold * 0.03);

            $maxCurrencies = new CurrencyLimit($goldRush, CurrencyType::GOLD);

            $type = CurrenciesMessageTypes::GOLD_RUSH;

            if ($maxCurrencies->canNotGiveCurrency()) {
                $event->character->gold = CurrencyLimit::MAX_GOLD;
                $event->character->save();

                $type = CurrenciesMessageTypes::GOLD_CAPPED;
            } else {
                $event->character->gold = $goldRush;
                $event->character->save();
            }

            $character = $event->character->refresh();

            ServerMessageHandler::handleMessage($character->user, $type, number_format($goldRush));

            event(new UpdateCharacterBaseDetailsEvent($character));
        }
    }
}
