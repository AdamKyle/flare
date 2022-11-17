<?php

namespace App\Game\Core\Services;

use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class GoldRush {

    public function processPotentialGoldRush(Character $character, Monster $monster) {
        if ($character->gold === MaxCurrenciesValue::MAX_GOLD) {
            return;
        }

        $gameMapBonus = $this->getGameMapBonus($character);

        if (GoldRushCheckCalculator::fetchGoldRushChance($monster, $gameMapBonus)) {
            $this->giveGoldRush($character);
        }
    }

    protected function giveGoldRush(Character $character) {
        $goldRush      = $character->gold + ($character->gold * 0.05);

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

        event(new ServerMessageEvent($character->user, $type, number_format($character->gold)));
    }

    protected function getGameMapBonus(Character $character): float {
        $gameMap        = $character->map->gameMap;
        $gameMapBonus   = 0.0;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        return $gameMapBonus;
    }
}
