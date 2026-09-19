<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyType;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Facades\App\Game\Core\Chance\GoldRushCheckCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GoldRush
{
    /**
     * Roll for and apply a potential Gold Rush bonus on top of the gold already gained.
     *
     * @param Character $character
     * @param int $goldGained
     * @param bool $dispatchCurrencyUpdate
     * @return void
     */
    public function processPotentialGoldRush(Character $character, int $goldGained, bool $dispatchCurrencyUpdate = true): void
    {
        if ($goldGained <= 0) {
            return;
        }

        if ($character->gold >= CurrencyLimit::MAX_GOLD) {
            return;
        }

        if (GoldRushCheckCalculator::fetchGoldRushChance($this->getGameMapBonus($character), 0.0)) {
            $this->giveGoldRush($character, $goldGained);

            if ($dispatchCurrencyUpdate && ! $character->is_auto_battling && $character->isLoggedIn()) {
                event(new UpdateCharacterCurrenciesEvent($character->refresh()));
            }
        }
    }

    /**
     * Give the Character a Gold Rush bonus of one twentieth of the gold gained.
     *
     * @param Character $character
     * @param int $goldGained
     * @return void
     */
    private function giveGoldRush(Character $character, int $goldGained): void
    {

        $amountGiven = intdiv($goldGained, 20);

        $goldRush = $character->gold + $amountGiven;

        $maxCurrencies = new CurrencyLimit($goldRush, CurrencyType::GOLD);

        $type = CurrenciesMessageTypes::GOLD_RUSH;

        if ($maxCurrencies->canNotGiveCurrency()) {
            $character->gold = CurrencyLimit::MAX_GOLD;
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
     * Resolve the current Game Map's drop chance bonus applied to the Gold Rush roll.
     *
     * @param Character $character
     * @return float
     */
    private function getGameMapBonus(Character $character): float
    {
        $gameMap = $character->map->gameMap;

        if (is_null($gameMap->drop_chance_bonus)) {
            return 0.0;
        }

        return $gameMap->drop_chance_bonus;
    }
}
