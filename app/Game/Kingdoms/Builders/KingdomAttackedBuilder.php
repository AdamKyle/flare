<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomLog;

class KingdomAttackedBuilder {

    /**
     * @var KingdomLog $log
     */
    private $log;

    /**
     * Sets the log.
     *
     * @param KingdomLog $log
     * @return KingdomAttackedBuilder
     */
    public function setLog(KingdomLog $log): KingdomAttackedBuilder {
        $this->log = $log;

        return $this;
    }

    public function fetchBuildingDamageReport(): array {
        $oldDefenderBuildings = $this->log->old_defender['buildings'];
        $newDefenderBuildings = $this->log->new_defender['buildings'];

        $buildingChanges = [];

        foreach ($newDefenderBuildings as $index => $building) {
            $oldDurability = $oldDefenderBuildings[$index]['durability'];
            $newDurability = $building['durability'];
            $buildingName  = $building['name'];

            if ($newDurability === $oldDurability) {
                $buildingChanges[$buildingName] = [
                    'has_fallen'      => false,
                    'old_durability'  => $oldDurability,
                    'new_durability'  => $newDurability,
                    'durability_lost' => 0,
                ];
            } else if ($newDurability === 0) {
                $buildingChanges[$buildingName] = [
                    'has_fallen'      => true,
                    'old_durability'  => $oldDurability,
                    'new_durability'  => $newDurability,
                    'durability_lost' => 1,
                ];
            } else {
                $percentage = 1 - ($newDurability / $oldDurability);

                $buildingChanges[$buildingName] = [
                    'has_fallen'      => false,
                    'old_durability'  => $oldDurability,
                    'new_durability'  => $newDurability,
                    'durability_lost' => $percentage,
                ];
            }
        }

        return $buildingChanges;
    }

    public function fetchUnitDamageReport(): array {
        $oldDefenderUnits = $this->log->old_defender['units'];
        $newDefenderUnits = $this->log->new_defender['units'];

        $unitChanges = [];

        foreach ($oldDefenderUnits as $index => $unitInfo) {
            $oldAmount = $unitInfo['amount'];
            $newAmount = $newDefenderUnits[$index]['amount'];

            $unitName = GameUnit::find($unitInfo['game_unit_id'])->name;

            if ($oldAmount === $newAmount) {
                $unitChanges[$unitName] = [
                    'lost_all'    => false,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'lost'        => 0,
                ];
            } else if ($newAmount === 0) {
                $unitChanges[$unitName] = [
                    'lost_all'    => true,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'lost'        => 1,
                ];
            } else {
                $percentage = 1 - ($newAmount / $oldAmount);

                $unitChanges[$unitName] = [
                    'lost_all'    => true,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'lost'        => $percentage,
                ];
            }
        }

        return $unitChanges;
    }
}
