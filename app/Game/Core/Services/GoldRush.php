<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GoldRush
{
    /**
     * Process a potential gold rush.
     *
     * @throws \Exception
     */
    public function processPotentialGoldRush(Character $character): void
    {

        if ($character->gold >= MaxCurrenciesValue::MAX_GOLD) {
            return;
        }

        if ($character->gold === MaxCurrenciesValue::MAX_GOLD) {
            return;
        }

        $gameMapBonus = $this->getGameMapBonus($character);

        if (GoldRushCheckCalculator::fetchGoldRushChance($gameMapBonus)) {
            $this->giveGoldRush($character);

            if (! $character->is_auto_battling && $character->isLoggedIn()) {
                event(new UpdateCharacterCurrenciesEvent($character->refresh()));
            }
        }
    }

    /**
     * Give the player a gold rush.
     *
     * @throws \Exception
     */
    protected function giveGoldRush(Character $character): void
    {
        $goldRush = $character->gold + ($character->gold * 0.05);

        $maxCurrencies = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

        $type = 'gold_rush';

        if ($maxCurrencies->canNotGiveCurrency()) {
            $character->gold = MaxCurrenciesValue::MAX_GOLD;
            $character->save();

            $type = 'gold_capped';
        } else {
            $character->gold = $goldRush;
            $character->save();
        }

        $character = $character->refresh();

        ServerMessageHandler::handleMessage($character->user, $type, number_format($character->gold));
    }

    /**
     * Get the gameMap Bonus.
     */
    protected function getGameMapBonus(Character $character): float
    {
        $gameMap = $character->map->gameMap;
        $gameMapBonus = 0.0;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        return $gameMapBonus;
    }
}
