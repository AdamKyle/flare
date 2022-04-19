<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\Traits\AttackHandlerCalculations;


class SiegeHandler {

    use AttackHandlerCalculations;

    /**
     * @var AttackHandler $attackHandler
     */
    private $attackHandler;

    /**
     * SiegeHandler constructor.
     *
     * @param AttackHandler $attackHandler
     */
    public function __construct(AttackHandler $attackHandler) {
        $this->attackHandler = $attackHandler;
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
                    'time_to_return' => $unitInfo['time_to_return'],
                    'settler'        => false,
                    'is_cannons'     => $gameUnit->name === 'Cannon',
                ];
            }
        }

        return $siegeUnits;
    }

    /**
     * Attack
     *
     * Starts with the primary target, moves to attacking fall back building and then attacks all
     * kingdom buildings assuming there are siege units left.
     *
     * @param Kingdom $defender
     * @param array $siegeUnits
     * @param array $healers
     * @return array
     */
    public function attack(Kingdom $defender, array $siegeUnits) {
        foreach ($siegeUnits as $index => $unitInfo) {
            $unitInfo = $this->attackHandler->primaryAttack($defender, $unitInfo);

            if ($unitInfo['amount'] > 0) {
                if ($unitInfo['fall_back'] === 'Buildings') {
                    $unitInfo = $this->attackHandler->attackKingdomBuildings($defender, $unitInfo);
                } else {
                    $unitInfo = $this->attackHandler->fallBackAttack($defender, $unitInfo);
                }
            }

            if ($unitInfo['amount'] > 0) {
                $unitInfo = $this->attackHandler->unitAttack($defender, $unitInfo);
            }

            $siegeUnits[$index] = $unitInfo;
        }

        return $siegeUnits;
    }
}
