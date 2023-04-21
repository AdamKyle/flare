<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Game\Mercenaries\Values\MercenaryValue;

trait MercenaryBonus {

    /**
     * Get Shard Bonus.
     *
     * @param Character $character
     * @return float
     */
    protected function getShardBonus(Character $character): float {

        $mercenary = $character->mercenaries()->where('mercenary_type', MercenaryValue::CHILD_OF_SHARDS)->first();

        if (!is_null($mercenary)) {
            return $mercenary->type()->getBonus($mercenary->current_level, $mercenary->reincarnated_bonus);
        }

        return 0;
    }

    /**
     * Get Gold Dust Bonus.
     *
     * @param Character $character
     * @return float
     */
    protected function getGoldDustBonus(Character $character): float {

        $mercenary = $character->mercenaries()->where('mercenary_type', MercenaryValue::CHILD_OF_GOLD_DUST)->first();

        if (!is_null($mercenary)) {
            return $mercenary->type()->getBonus($mercenary->current_level, $mercenary->reincarnated_bonus);
        }

        return 0;
    }

    /**
     * Get Copper Coin Bonus.
     *
     * @param Character $character
     * @return float
     */
    protected function getCopperCoinBonus(Character $character): float {

        $mercenary = $character->mercenaries()->where('mercenary_type', MercenaryValue::CHILD_OF_COPPER_COINS)->first();

        if (!is_null($mercenary)) {
            return $mercenary->type()->getBonus($mercenary->current_level, $mercenary->reincarnated_bonus);
        }

        return 0;
    }

    /**
     * Get Gambling Bonus.
     *
     * @param Character $character
     * @return float
     */
    protected function getGamblerBonus(Character $character): float {

        $mercenary = $character->mercenaries()->where('mercenary_type', MercenaryValue::CHILD_OF_GAMBLING)->first();

        if (!is_null($mercenary)) {
            return $mercenary->type()->getBonus($mercenary->current_level, $mercenary->reincarnated_bonus);
        }

        return 0;
    }
}
