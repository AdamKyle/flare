<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Builders\AttackedKingdomBuilder;
use App\Game\Kingdoms\Builders\KingdomAttackedBuilder;

class KingdomLogService {

    /**
     * @var KingdomLog $log
     */
    private $log;

    /**
     * @var KingdomAttackedBuilder $kingdomAttacked
     */
    private $kingdomAttacked;

    /**
     * @var AttackedKingdomBuilder $attackedKingdom
     */
    private $attackedKingdom;

    /**
     * KingdomLogService constructor.
     *
     * @param KingdomAttackedBuilder $kingdomAttacked
     * @param AttackedKingdomBuilder $attackedKingdom
     */
    public function __construct(KingdomAttackedBuilder $kingdomAttacked, AttackedKingdomBuilder $attackedKingdom) {
        $this->kingdomAttacked = $kingdomAttacked;
        $this->attackedKingdom = $attackedKingdom;
    }

    /**
     * Sets the log.
     *
     * @param KingdomLog $log
     * @return KingdomLogService
     */
    public function setLog(KingdomLog $log): KingdomLogService {
        $this->log = $log;

        return $this;
    }

    public function attackReport(): array {
        $value = new KingdomLogStatusValue($this->log->status);

        $data = [];

        if ($value->kingdomWasAttacked()) {
            $kingdomAttacked   = $this->kingdomAttacked->setLog($this->log);

            $data['kingdom']   = $this->fetchKingdomInformation();
            $data['buildings'] = $kingdomAttacked->fetchBuildingDamageReport();
            $data['units']     = $kingdomAttacked->fetchUnitDamageReport();
        } else if ($value->attackedKingdom()) {
            $attackedKingdom = $this->attackedKingdom->setLog($this->log);

            $data['units']   = $attackedKingdom->attackedKingdomReport();
        } else if ($value->lostAttack()) {
            $attackedKingdom = $this->attackedKingdom->setLog($this->log);

            $data['units']   = $attackedKingdom->lostAttack();
        }

        return $data;
    }

    protected function fetchKingdomInformation() {
        $oldDefender = $this->log->old_defender;
        $newDefender = $this->log->new_defender;

        $kingdom = Kingdom::find($oldDefender['id']);

        $moraleIncrease = 0;
        $moraleDecrease = 0;

        foreach($kingdom->buildings as $building) {
            if ($building->current_durability > 0) {
                $moraleIncrease += $building->morale_increase;
            } else if ($building->current_durability === 0) {
                $moraleDecrease += $building->morale_decrease;
            }
        }

        return [
            'old_morale'      => $oldDefender['current_morale'],
            'new_morale'      => $newDefender['current_morale'],
            'morale_increase' => $moraleIncrease,
            'morale_decrease' => $moraleDecrease,
        ];
    }

}
