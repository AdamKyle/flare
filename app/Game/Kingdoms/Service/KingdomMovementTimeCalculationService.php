<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class KingdomMovementTimeCalculationService {


    public function __construct(private readonly DistanceCalculation $distanceCalculation) {}

    /**
     * Get the time (seconds) from one kingdom to another.
     *
     * @param Character $character
     * @param Kingdom $kingdomAskingForResources
     * @param Kingdom $requestingFromKingdom
     * @return int
     */
    public function getTimeToKingdom(Character $character, Kingdom $kingdomAskingForResources, Kingdom $requestingFromKingdom):int {
        $pixelDistance = $this->distanceCalculation->calculatePixel(
            $kingdomAskingForResources->x_position,
            $kingdomAskingForResources->y_position,
            $requestingFromKingdom->x_position,
            $requestingFromKingdom->y_position
        );

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

        $skill = $character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION)->first();

        $timeToKingdom -= ($timeToKingdom * $skill->resource_request_time_reduction);

        if ($timeToKingdom <= 0) {
            $timeToKingdom = 1;
        }

        return $timeToKingdom;
    }
}
