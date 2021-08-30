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
            $oldDurability = $oldDefenderBuildings[$index]['current_durability'];
            $newDurability = $building['current_durability'];
            $buildingName  = $building['name'];

            if (($newDurability === 0 && $oldDurability !== 0) && ($newDurability !== $oldDurability)) {
                $buildingChanges[$buildingName] = [
                    'has_fallen'             => true,
                    'old_durability'         => $oldDurability,
                    'new_durability'         => $newDurability,
                    'durability_lost'        => 1,
                    'decreases_morale'       => $this->decreasesMorale($building),
                    'affects_morale'         => $this->affectsMorale($building),
                    'decrease_morale_amount' => $building['game_building']['decrease_morale_amount'],
                    'increase_morale_amount' => $building['game_building']['increase_morale_amount'],
                ];
            } else if (($newDurability !== 0 && $oldDurability !== 0) && ($newDurability !== $oldDurability)) {
                $percentage = 1 - ($newDurability / $oldDurability);

                $buildingChanges[$buildingName] = [
                    'has_fallen'             => false,
                    'old_durability'         => $oldDurability,
                    'new_durability'         => $newDurability,
                    'durability_lost'        => $percentage,
                    'decreases_morale'       => false,
                    'affects_morale'         => $this->affectsMorale($building),
                    'decrease_morale_amount' => $building['game_building']['decrease_morale_amount'],
                    'increase_morale_amount' => $building['game_building']['increase_morale_amount'],
                ];
            }
        }

        return $buildingChanges;
    }

    public function fetchUnitKillReport(): array {
        $oldUnits = $this->log->units_sent;
        $unitsSurvived = $this->log->units_survived;

        $unitLosses = [];

        if (is_null($oldUnits)) {
         return $unitLosses;
        }

        foreach ($oldUnits as $index => $unit) {
            $amountLeft = $unitsSurvived[$index]['amount'];

            if ($amountLeft > 0) {
                if ($amountLeft === $unit['amount']) {
                    $amountLeft = 0.0;
                } else {
                    $amountLeft = number_format($amountLeft / $unit['amount'], 2);
                }
            } else {
                $amountLeft = 1.0;
            }

            $unitLosses[GameUnit::find($unit['unit_id'])->name] = [
                'amount_killed' => $amountLeft,
            ];
        }

        return $unitLosses;
    }

    public function fetchUnitDamageReport(): array {
        $oldDefenderUnits = $this->log->old_defender['units'];
        $newDefenderUnits = $this->log->new_defender['units'];

        $unitChanges = [];

        foreach ($oldDefenderUnits as $index => $unitInfo) {
            $oldAmount = $unitInfo['amount'];
            $newAmount = $newDefenderUnits[$index]['amount'];

            $unitName = GameUnit::find($unitInfo['game_unit_id'])->name;

            if (($newAmount === 0 && $oldAmount !== 0) && $newAmount !== $oldAmount) {
                $unitChanges[$unitName] = [
                    'lost_all'    => true,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'lost'        => 1,
                ];
            } else if (($newAmount !== 0 && $oldAmount !== 0) && $newAmount !== $oldAmount) {
                $percentage = 1 - ($newAmount / $oldAmount);

                $unitChanges[$unitName] = [
                    'lost_all'    => $percentage <= 0.0,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'lost'        => number_format($percentage, 2),
                ];
            }
        }

        return $unitChanges;
    }

    protected function decreasesMorale(array $building): bool {
        return $building['current_durability'] === 0 && $building['game_building']['decrease_morale_amount'] > 0;
    }

    protected function affectsMorale(array $building): bool {
        return $building['game_building']['decrease_morale_amount'] > 0 && $building['game_building']['increase_morale_amount'] > 0;
    }

}
