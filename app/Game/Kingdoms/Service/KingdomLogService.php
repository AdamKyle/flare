<?php

namespace App\Game\Kingdoms\Service;

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

            $data['buildings'] = $kingdomAttacked->fetchBuildingDamageReport();
            $data['units']     = $kingdomAttacked->fetchUnitDamageReport();
        } else if ($value->attackedKingdom()) {
            $attackedKingdom = $this->attackedKingdom->setLog($this->log);

            $data['units'] = $attackedKingdom->attackedKingdomReport();
        }

        return $data;
    }

}
