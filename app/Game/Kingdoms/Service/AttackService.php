<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Handlers\UnitHandler;
use App\Game\Kingdoms\Handlers\SiegeHandler;

class AttackService {

    /**
     * @var SiegeHandler $seigeHandler
     */
    private $siegeHandler;

    /**
     * @var UnitHandler $unitHandler
     */
    private $unitHandler;

    /**
     * @var KingdomResourcesService $kingdomResourcesService
     */
    private $kingdomResourcesService;

    /**
     * AttackService constructor.
     *
     * @param SiegeHandler $siegeHandler
     * @param UnitHandler $unitHandler
     * @param KingdomResourcesService $kingdomResourcesService
     */
    public function __construct(SiegeHandler $siegeHandler, UnitHandler $unitHandler, KingdomResourcesService  $kingdomResourcesService) {
        $this->siegeHandler            = $siegeHandler;
        $this->unitHandler             = $unitHandler;
        $this->kingdomResourcesService = $kingdomResourcesService;
    }

    /**
     * Handles the actual attack.
     *
     * @param UnitMovementQueue $unitMovement
     * @param int $defenderId
     */
    public function attack(UnitMovementQueue $unitMovement, int $defenderId) {
        $attackingUnits = $unitMovement->units_moving;
        $defender       = Kingdom::where('id', $defenderId)
                                 ->where('x_position', $unitMovement->moving_to_x)
                                 ->where('y_position', $unitMovement->moving_to_y)
                                 ->first();

        $siegeUnits   = $this->fetchSiegeUnits($attackingUnits);
        $regularUnits = $this->getRegularUnits($attackingUnits);

        if (!empty($siegeUnits)) {
            $newSiegeUnits   = $this->siegeHandler->attack($defender, $siegeUnits);
        }

        if (!empty($regularUnits)) {
            $newRegularUnits = $this->unitHandler->attack($defender, $regularUnits);
        }

        $defender = $defender->refresh();

        $this->kingdomResourcesService->setKingdom($defender)->increaseOrDecreaseMorale();
    }

    /**
     * Fetches the siege units.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function fetchSiegeUnits(array $attackingUnits): array {
        $siegeUnits = [];

        forEach($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('siege_weapon', true)->first();

            if (!is_null($gameUnit)) {
                $siegeUnits[] = [
                    'amount'         => $unitInfo['amount'],
                    'total_attack'   => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'  => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target' => $gameUnit->primary_target,
                    'fall_back'      => $gameUnit->fall_back,
                    'unit_id'        => $gameUnit->id,
                ];
            }
        }

        return $siegeUnits;
    }

    /**
     * Gets the regular non siege units.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function getRegularUnits(array $attackingUnits): array {
        $regularUnits = [];

        forEach($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('siege_weapon', false)->first();

            if (!is_null($gameUnit)) {
                $regularUnits[] = [
                    'amount'         => $unitInfo['amount'],
                    'total_attack'   => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'  => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target' => $gameUnit->primary_target,
                    'fall_back'      => $gameUnit->fall_back,
                    'unit_id'        => $gameUnit->id,
                ];
            }
        }

        return $regularUnits;
    }
}
