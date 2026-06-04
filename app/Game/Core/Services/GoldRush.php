<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use App\Flare\Values\MaxCurrenciesValue;
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
     * @throws Exception
     */
    public function processPotentialGoldRush(Character $character, int $goldGained): void
    {
        if ($goldGained <= 0) {
            return;
        }

        if ($character->gold >= MaxCurrenciesValue::MAX_GOLD) {
            return;
        }

        if (GoldRushCheckCalculator::fetchGoldRushChance($this->getGameMapBonus($character), $this->getLocationBonus($character))) {
            $this->giveGoldRush($character, $goldGained);

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
    private function giveGoldRush(Character $character, int $goldGained): void
    {

        $amountGiven = (int) floor($goldGained * 0.05);

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

    private function getGameMapBonus(Character $character): float
    {
        $gameMap = $character->map->gameMap;

        if (is_null($gameMap->drop_chance_bonus)) {
            return 0.0;
        }

        return $gameMap->drop_chance_bonus;
    }

    private function getLocationBonus(Character $character): float
    {
        $map = $character->map;

        $location = Location::whereNotNull('enemy_strength_increase')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->first();

        if (is_null($location)) {
            return 0.0;
        }

        return LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($location->enemy_strength_increase);
    }
}
