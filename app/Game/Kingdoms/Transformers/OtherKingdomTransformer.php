<?php

namespace App\Game\Kingdoms\Transformers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use League\Fractal\TransformerAbstract;

class OtherKingdomTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Kingdom $kingdom): array
    {
        return [
            'id' => $kingdom->id,
            'name' => $kingdom->name,
            'game_map_id' => $kingdom->game_map_id,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'current_morale' => $kingdom->current_morale,
            'max_morale' => $kingdom->max_morale,
            'treasury' => $kingdom->treasury,
            'gold_bars' => $kingdom->gold_bars,
            'treasury_defence' => $kingdom->treasury / KingdomMaxValue::MAX_TREASURY,
            'passive_defence' => $kingdom->fetchDefenceBonusFromPassive(),
            'treasury_defence' => $kingdom->fetchTreasuryDefenceBonus(),
            'walls_defence' => $kingdom->getWallsDefence(),
            'gold_bars_defence' => $kingdom->fetchGoldBarsDefenceBonus(),
            'defence_bonus' => $kingdom->fetchKingdomDefenceBonus(),
            'npc_owned' => $kingdom->npc_owned,
        ];
    }
}
