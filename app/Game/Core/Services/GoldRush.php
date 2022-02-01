<?php

namespace App\Game\Core\Services;

use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class GoldRush {

    public function processPotentialGoldRush(Character $character, Monster $monster, Adventure $adventure = null) {
        if ($character->gold === MaxCurrenciesValue::MAX_GOLD) {
            return;
        }

        $gameMapBonus = $this->getGameMapBonus($character);

        if (GoldRushCheckCalculator::fetchGoldRushChance($monster, $gameMapBonus, $adventure)) {
            $this->giveGoldRush($character);
        }
    }

    protected function giveGoldRush(Character $character) {
        $goldRush      = ceil($character->gold + ($character->gold * 0.05));
        dump($goldRush);
        $maxCurrencies = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

        $type = 'gold_rush';

        if ($maxCurrencies->canNotGiveCurrency()) {
            dump('no');
            $character->gold = MaxCurrenciesValue::MAX_GOLD;
            $character->save();

            $type = 'gold_capped';
        } else {
            $character->gold += $goldRush;
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