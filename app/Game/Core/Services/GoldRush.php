<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Exception;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GoldRush
{

    /**
     * Process potential gold rush
     *
     * @param Character $character
     * @return void
     * @throws Exception
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
     * @throws Exception
     */
    private function giveGoldRush(Character $character): void
    {

        $amountGiven = ($character->gold * 0.05);

        $goldRush = $character->gold + $amountGiven;

        $maxCurrencies = new MaxCurrenciesValue($goldRush, MaxCurrenciesValue::GOLD);

        $type = CurrenciesMessageTypes::GOLD_RUSH;

        if ($maxCurrencies->canNotGiveCurrency()) {
            $character->gold = MaxCurrenciesValue::MAX_GOLD;
            $character->save();

            $type = CurrenciesMessageTypes::GOLD_CAPPED;
        } else {
            $character->gold = $goldRush;
            $character->save();
        }

        $character = $character->refresh();

        ServerMessageHandler::handleMessageWithNewValue($character->user, $type, number_format($amountGiven), number_format($character->gold));
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
