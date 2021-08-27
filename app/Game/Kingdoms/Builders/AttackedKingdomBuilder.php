<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomLog;

class AttackedKingdomBuilder {

    /**
     * @var KingdomLog $log
     */
    private $log;

    /**
     *
     * Sets the log.
     *
     * @param KingdomLog $log
     * @return AttackedKingdomBuilder
     */
    public function setLog(KingdomLog $log): AttackedKingdomBuilder {
        $this->log = $log;

        return $this;
    }

    public function attackedKingdomReport(): array {
        return $this->unitAttackInfo();
    }

    public function fetchUnitDamageReport(): array {
        $oldUnits = $this->log->old_defender['units'];
        $newUnits = $this->log->new_defender['units'];

        $unitLosses = [];

        foreach ($oldUnits as $index => $unit) {

            $amountLeft = $newUnits[$index]['amount'];

            if ($amountLeft > 0) {
                if ($amountLeft === $unit['amount']) {
                    $amountLeft = 0.0;
                } else {
                    $amountLeft = number_format($amountLeft / $unit['amount'], 2);
                }
            } else {
                $amountLeft = 1.0;
            }

            $unitLosses[GameUnit::find($unit['game_unit_id'])->name] = [
                'amount_killed' => $amountLeft,
            ];
        }

        return $unitLosses;
    }

    public function fetchBuildingsDamageReport(): array {
        $oldBuildings = $this->log->old_defender['buildings'];
        $newBuildings = $this->log->new_defender['buildings'];

        $buildingLosses = [];

        foreach ($oldBuildings as $index => $building) {
            $amountLeft = $newBuildings[$index]['current_durability'];

            if ($amountLeft > 0) {
                if ($amountLeft === $building['current_durability']) {
                    $amountLeft = 0.0;
                } else {
                    $amountLeft = number_format($amountLeft / $building['current_durability'], 2);
                }
            } else {
                $amountLeft = 1.0;
            }

            $buildingLosses[GameBuilding::find($building['game_building_id'])->name] = [
                'durability_lost' => $amountLeft,
            ];
        }

        return $buildingLosses;
    }

    public function lostAttack(): array {
        return $this->unitAttackInfo();
    }

    protected function unitAttackInfo(): array {
        $unitsSent     = $this->log->units_sent;
        $unitsSurvived = $this->log->units_survived;

        $unitChanges = [];

        foreach ($unitsSent as $index => $unitInfo) {
            $oldAmount = $unitInfo['amount'];
            $newAmount = $unitInfo['settler'] ? 0 : $unitsSurvived[$index]['amount'];
            $unitName  = GameUnit::find($unitInfo['unit_id'])->name;

            $coreChanges = [
                'total_attack'   => $unitInfo['total_attack'],
                'total_defence'  => $unitInfo['total_defence'],
                'settler'        => $unitInfo['settler'],
                'primary_target' => $unitInfo['primary_target'],
                'fall_back'      => $unitInfo['fall_back'],
            ];

            if (isset($unitInfo['heal_for']) && isset($unitInfo['healer'])) {
                $coreChanges = array_merge($coreChanges, [
                    'total_heal' => ($unitInfo['heal_for'] * $unitInfo['amount']),
                    'healer'     => $unitInfo['healer'],
                ]);
            }

            if (($newAmount === 0 && $oldAmount !== 0) && ($oldAmount !== $newAmount)) {
                $changes = [
                    'lost_all'       => true,
                    'old_amount'     => $oldAmount,
                    'new_amount'     => $newAmount,
                    'lost'           => 1,
                ];

                $unitChanges[$unitName] = array_merge($coreChanges, $changes);
            } else if (($newAmount !== 0 && $oldAmount !== 0) && ($oldAmount !== $newAmount)) {
                $percentage = 1 - ($newAmount / $oldAmount);

                $changes = [
                    'lost_all'       => false,
                    'old_amount'     => $oldAmount,
                    'new_amount'     => $newAmount,
                    'lost'           => number_format($percentage, 2),
                ];

                $unitChanges[$unitName] = array_merge($coreChanges, $changes);
            }
        }

        return $unitChanges;
    }
}
