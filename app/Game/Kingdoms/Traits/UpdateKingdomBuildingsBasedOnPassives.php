<?php

namespace App\Game\Kingdoms\Traits;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;

trait UpdateKingdomBuildingsBasedOnPassives {

    /**
     * Update weather the buildings are locked or not.
     *
     * - Used for purchasing a kingdom.
     *
     * @param Kingdom $kingdom
     * @return Kingdom
     */
    public function updateBuildings(Kingdom $kingdom): Kingdom {
        $character = $kingdom->character;

        foreach(GameBuilding::all() as $building) {

            $isLocked = $building->is_locked;

            if ($isLocked) {
                $passive = $character->passiveSkills()->where('passive_skill_id', $building->passive_skill_id)->first();

                if (!is_null($passive)) {
                    if ($passive->current_level === $building->level_required) {
                        $building = $kingdom->buildings->where('game_building_id', $building->id)->first();

                        $building->update([
                            'is_locked' => false,
                        ]);
                    }
                }
            }
        }

        return $kingdom->refresh();
    }
}
