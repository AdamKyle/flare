<?php

namespace App\Game\Kingdoms\Builders;

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
                $coreChanges[] = [
                    'total_heal' => ($unitInfo['heal_for'] * $unitInfo['amount']),
                    'healer'     => $unitInfo['healer'],
                ];
            }

            if ($oldAmount === $newAmount) {
                $changes = [
                    'lost_all'       => false,
                    'old_amount'     => $oldAmount,
                    'new_amount'     => $newAmount,
                    'lost'           => 0,
                ];

                $unitChanges[$unitName] = array_merge($coreChanges, $changes);
            } else if ($newAmount === 0) {
                $changes = [
                    'lost_all'       => true,
                    'old_amount'     => $oldAmount,
                    'new_amount'     => $newAmount,
                    'lost'           => 1,
                ];

                $unitChanges[$unitName] = array_merge($coreChanges, $changes);
            } else {
                $percentage = 1 - ($newAmount / $oldAmount);

                $changes = [
                    'lost_all'       => false,
                    'old_amount'     => $oldAmount,
                    'new_amount'     => $newAmount,
                    'lost'           => $percentage,
                ];

                $unitChanges[$unitName] = array_merge($coreChanges, $changes);
            }
        }

        return $unitChanges;
    }
}
