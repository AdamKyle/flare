<?php

namespace App\Admin\Services;

use Exception;
use App\Admin\Jobs\AssignNewKingdomBuildingsJob;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;

class UpdateKingdomsService {

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function cleanRequestData(array $data) : array{

        $data = $this->checkForIncreaseInResources($data);
        $data = $this->checkForIncreaseInResources($data);

        try {
            $data = $this->checkForUnitRecruitment($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $data;
    }

    /**
     * Clean up the increase resources section.
     *
     * @param array $data
     * @return array
     */
    protected function checkForIncreaseInResources(array $data): array {
        $resourcesIncreaseAttributes = [
            'increase_wood_amount',
            'increase_clay_amount',
            'increase_stone_amount',
            'increase_iron_amount',
        ];

        $originalLength = count($resourcesIncreaseAttributes);

        if (filter_var($data['is_resource_building'], FILTER_VALIDATE_BOOLEAN)) {
            foreach ($resourcesIncreaseAttributes as $attribute) {
                if ((int) $data[$attribute] !== 0) {
                    unset($resourcesIncreaseAttributes[$attribute]);
                }
            }
        }

        // If the length of the modified array is the same as the original length
        // we say we increase resources, but don't ...
        if ($originalLength === count($resourcesIncreaseAttributes)) {
            $data['is_resource_building'] = 0;

            foreach ($resourcesIncreaseAttributes as $attribute) {
                $data['$attribute'] = 0;
            }
        }

        return $data;
    }

    /**
     * Check if we have units to recruit.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function checkForUnitRecruitment(array $data): array {

        if (filter_var($data['trains_units'], FILTER_VALIDATE_BOOLEAN)) {
            if (!isset($data['units_to_recruit'])) {
                $data['trains_units'] = 0;
                $data['units_per_level'] = null;
                $data['only_at_level'] = null;
            } else {
                $perLevel  = (int) $data['units_per_level'];
                $maxLevel  = (int) $data['max_level'];
                $onlyLevel = (int) $data['only_at_level'];

                if (!is_null($perLevel)) {
                    if ((count($data['units_to_recruit']) * $perLevel) > $maxLevel) {
                        throw new Exception('the amount of units x the per level is greator then your max building level.');
                    }
                }

                if ($perLevel !== 0 && $onlyLevel !== 0) {
                    throw new Exception('Building units cannot be recruited at a specific level AND per level. one or the other.');
                }
            }
        }

        return $data;
    }

    /**
     * Updates or creates the kingdom building for players.
     *
     * Called from the livewire admin view to handle giving new buildings to players.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int $levels
     */
    public function updateKingdomKingdomBuildings(GameBuilding $gameBuilding, $selectedUnits = [], int $levels = 0): void
    {

        if (empty($selectedUnits)) {
            return;
        }

        $gameBuilding = $this->reassignUnits($gameBuilding, $selectedUnits, $levels);

        UpdateKingdomBuildings::dispatch($gameBuilding, $selectedUnits, $levels)->delay(now()->addMinutes(1));
    }

    /**
     * Assigns the new building to players.
     *
     * Done on import.
     *
     * @param GameBuilding $gameBuilding
     */
    public function assignNewBuildingsToCharacters(GameBuilding $gameBuilding) {
        AssignNewKingdomBuildingsJob::dispatch($gameBuilding);
    }

    /**
     * Reassigns the game units to a game building.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int $levels
     * @return GameBuilding
     */
    protected function reassignUnits(GameBuilding $gameBuilding, array $selectedUnits = [], int $levels = 0): GameBuilding {
        if (empty($selectedUnits)) {
            return $gameBuilding;
        }

        if ($gameBuilding->units->isNotEmpty()) {
            foreach($gameBuilding->units as $unit) {
                $unit->delete();
            }
        }

        $this->assignUnits($gameBuilding->refresh(), $selectedUnits, $levels);

        return $gameBuilding->refresh();
    }

    /**
     * Assigns the units to the building.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int $levels
     * @return void
     */
    private function assignUnits(GameBuilding $gameBuilding, array $selectedUnits, int $levels): void {
        $gameBuilding->units()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $selectedUnits[0],
            'required_level'   => !is_null($gameBuilding->only_at_level) ? $gameBuilding->only_at_level : 1,
        ]);

        unset($selectedUnits[0]);

        $initialLevel = 1;

        if (empty($selectedUnits)) {
            return;
        }

        foreach($selectedUnits as $unitId) {
            $initialLevel += $levels;

            $gameBuilding->units()->create([
                'game_building_id' => $gameBuilding->id,
                'game_unit_id'     => $unitId,
                'required_level'   => $initialLevel,
            ]);
        }
    }
}
