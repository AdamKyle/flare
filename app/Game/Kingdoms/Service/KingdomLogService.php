<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Builders\AttackedKingdomBuilder;
use App\Game\Kingdoms\Builders\KingdomAttackedBuilder;
use App\Game\Kingdoms\Builders\TookKingdomBuilder;

class KingdomLogService
{
    /**
     * @var KingdomLog
     */
    private $log;

    /**
     * @var KingdomAttackedBuilder
     */
    private $kingdomAttacked;

    /**
     * @var AttackedKingdomBuilder
     */
    private $attackedKingdom;

    /**
     * @var TookKingdomBuilder
     */
    private $tookKingdom;

    /**
     * KingdomLogService constructor.
     */
    public function __construct(KingdomAttackedBuilder $kingdomAttacked, AttackedKingdomBuilder $attackedKingdom, TookKingdomBuilder $tookKingdom)
    {
        $this->kingdomAttacked = $kingdomAttacked;
        $this->attackedKingdom = $attackedKingdom;
        $this->tookKingdom = $tookKingdom;
    }

    /**
     * Sets the log.
     */
    public function setLog(KingdomLog $log): KingdomLogService
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Builds the attack report.
     *
     * @throws \Exception
     */
    public function attackReport(): array
    {
        $value = new KingdomLogStatusValue($this->log->status);

        $data = [];

        $oldDefender = $this->log->old_defender;
        $newDefender = $this->log->new_defender;

        if ($value->kingdomWasAttacked() || $value->bombsDropped()) {
            $kingdomAttacked = $this->kingdomAttacked->setLog($this->log);

            $data['kingdom'] = $this->fetchKingdomInformation($oldDefender, $newDefender);
            $data['buildings'] = $kingdomAttacked->fetchBuildingDamageReport();
            $data['units'] = $kingdomAttacked->fetchUnitDamageReport();
            $data['defender_units'] = $kingdomAttacked->fetchUnitKillReport();
            $data['defender_buildings'] = [];
        } elseif ($value->attackedKingdom() || $value->lostAttack()) {
            $attackedKingdom = $this->attackedKingdom->setLog($this->log);

            $data['units'] = $attackedKingdom->attackedKingdomReport();
            $data['defender_units'] = $attackedKingdom->fetchUnitDamageReport();
            $data['defender_buildings'] = $attackedKingdom->fetchBuildingsDamageReport();
        } elseif ($value->tookKingdom()) {
            $tookKingdom = $this->tookKingdom->setLog($this->log);

            $data = $tookKingdom->fetchChanges();

            $data['kingdom'] = $this->fetchKingdomInformation($oldDefender);
        }

        return $data;
    }

    /**
     * Fetches the kingdom information for the attack log.
     */
    protected function fetchKingdomInformation(array $oldDefender, array $newDefender = []): array
    {
        $kingdom = Kingdom::find($oldDefender['id']);

        $moraleIncrease = 0;
        $moraleDecrease = 0;

        foreach ($kingdom->buildings as $building) {
            if ($building->current_durability > 0) {
                $moraleIncrease += $building->morale_increase;
            } elseif ($building->current_durability === 0) {
                $moraleDecrease += $building->morale_decrease;
            }
        }

        $data = [
            'old_morale' => empty($newDefender) ? $kingdom->current_morale : $oldDefender['current_morale'],
            'morale_increase' => $moraleIncrease,
            'morale_decrease' => $moraleDecrease,
        ];

        if (! empty($newDefender)) {
            $data['new_morale'] = $newDefender['current_morale'];
        }

        return $data;
    }
}
