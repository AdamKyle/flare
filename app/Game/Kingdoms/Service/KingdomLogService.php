<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Builders\AttackedKingdomBuilder;
use App\Game\Kingdoms\Builders\KingdomAttackedBuilder;
use App\Game\Kingdoms\Builders\TookKingdomBuilder;

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
     * @var TookKingdomBuilder $tookKingdom
     */
    private $tookKingdom;

    /**
     * KingdomLogService constructor.
     *
     * @param KingdomAttackedBuilder $kingdomAttacked
     * @param AttackedKingdomBuilder $attackedKingdom
     */
    public function __construct(KingdomAttackedBuilder $kingdomAttacked, AttackedKingdomBuilder $attackedKingdom, TookKingdomBuilder $tookKingdom) {
        $this->kingdomAttacked = $kingdomAttacked;
        $this->attackedKingdom = $attackedKingdom;
        $this->tookKingdom    = $tookKingdom;
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

    /**
     * Builds the attack report.
     *
     * @return array
     * @throws \Exception
     */
    public function attackReport(): array {
        $value = new KingdomLogStatusValue($this->log->status);

        $data = [];

        $oldDefender = $this->log->old_defender;
        $newDefender = $this->log->new_defender;

        if ($value->kingdomWasAttacked() || $value->bombsDropped()) {
            $kingdomAttacked   = $this->kingdomAttacked->setLog($this->log);

            $data['kingdom']   = $this->fetchKingdomInformation($oldDefender, $newDefender);
            $data['buildings'] = $kingdomAttacked->fetchBuildingDamageReport();
            $data['units']     = $kingdomAttacked->fetchUnitDamageReport();
            $data['defender_units'] = $kingdomAttacked->fetchUnitKillReport();
            $data['defender_buildings'] = [];
        } else if ($value->attackedKingdom() || $value->lostAttack()) {
            $attackedKingdom            = $this->attackedKingdom->setLog($this->log);

            $data['units']              = $attackedKingdom->attackedKingdomReport();
            $data['defender_units']     = $attackedKingdom->fetchUnitDamageReport();
            $data['defender_buildings'] = $attackedKingdom->fetchBuildingsDamageReport();
        } else if ($value->tookKingdom()) {
            $tookKingdom = $this->tookKingdom->setLog($this->log);

            $data = $tookKingdom->fetchChanges();

            $data['kingdom'] = $this->fetchKingdomInformation($oldDefender);
        }

        return $data;
    }

    /**
     * Fetches the kingdom information for the attack log.
     *
     * @param array $oldDefender
     * @param array $newDefender
     * @return array
     */
    protected function fetchKingdomInformation(array $oldDefender, array $newDefender = []): array {
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

        $data = [
            'old_morale'      => empty($newDefender) ? $kingdom->current_morale : $oldDefender['current_morale'],
            'morale_increase' => $moraleIncrease,
            'morale_decrease' => $moraleDecrease,
        ];

        if (!empty($newDefender)) {
            $data['new_morale'] = $newDefender['current_morale'];
        }

        return $data;
    }

}
