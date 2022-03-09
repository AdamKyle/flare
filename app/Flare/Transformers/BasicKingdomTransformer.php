<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\PassiveSkill;
use App\Game\Kingdoms\Values\BuildingActions;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Kingdom;
use Illuminate\Support\Collection;

class BasicKingdomTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Kingdom $kingdom) {
        return [
            'id'                 => $kingdom->id,
            'name'               => $kingdom->name,
            'max_stone'          => $kingdom->max_stone,
            'max_wood'           => $kingdom->max_wood,
            'max_clay'           => $kingdom->max_clay,
            'max_iron'           => $kingdom->max_iron,
            'current_stone'      => $kingdom->current_stone,
            'current_wood'       => $kingdom->current_wood,
            'current_clay'       => $kingdom->current_clay,
            'current_iron'       => $kingdom->current_iron,
            'current_population' => $kingdom->current_population,
            'max_population'     => $kingdom->max_population,
            'x_position'         => $kingdom->x_position,
            'y_position'         => $kingdom->y_position,
            'current_morale'     => $kingdom->current_morale,
            'max_morale'         => $kingdom->max_morale,
            'treasury'           => $kingdom->treasury,
            'gold_bars'          => $kingdom->gold_bars,
            'treasury_defence'   => $kingdom->treasury / KingdomMaxValue::MAX_TREASURY,
            'passive_defence'    => $kingdom->fetchDefenceBonusFromPassive(),
            'treasury_defence'   => $kingdom->fetchTreasuryDefenceBonus(),
            'walls_defence'      => $kingdom->getWallsDefence(),
            'gold_bars_defence'  => $kingdom->fetchGoldBarsDefenceBonus(),
            'defence_bonus'      => $kingdom->fetchKingdomDefenceBonus(),
        ];
    }
}
