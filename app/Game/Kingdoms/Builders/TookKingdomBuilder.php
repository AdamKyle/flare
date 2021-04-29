<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomLog;

class TookKingdomBuilder {

    /**
     * @var KingdomLog $log
     */
    private $log;

    /**
     * Set the log.
     *
     * @param KingdomLog $log
     * @return $this
     */
    public function setLog(KingdomLog $log): TookKingdomBuilder {
        $this->log = $log;

        return $this;
    }

    public function fetchChanges(): array {
        $data = [];

        $data['units']   = $this->getUnitChanges();

        return $data;
    }

    protected function getUnitChanges(): array {
        $units = [];

        if (empty($this->log->old_defender['units'])) {
            foreach ($this->log->units_sent as $unitInfo) {
                if (!$unitInfo['settler']) {
                    $unit = GameUnit::find($unitInfo['unit_id']);

                    $units[$unit->name] = [
                        'old_amount' => 0,
                        'new_amount' => $unitInfo['amount'],
                        'gained'     => $unitInfo['amount'],
                    ];
                }
            }
        } else {
            foreach ($this->log->units_sent as $index => $unitInfo) {
                if (!$unitInfo['settler'] && isset($this->log->old_defender['units'][$index])) {

                    $unit      = GameUnit::find($unitInfo['unit_id']);
                    $oldAmount = $this->log->old_defender['units'][$index]['amount'];
                    $units[$unit->name] = [
                        'old_amount' => 0,
                        'new_amount' => $unitInfo['amount'] + $oldAmount,
                        'gained'     => $unitInfo['amount'],
                    ];
                }
            }
        }

        return $units;
    }
}
