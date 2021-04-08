<?php

namespace App\Game\Kingdoms\Handlers;

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
     * Attack
     *
     * Starts with the primary target, moves to attacking fall back building and then attacks all
     * kingdom buildings assuming their are siege units left.
     *
     * @param Kingdom $defender
     * @param array $siegeUnits
     * @param array $healers
     * @return array
     */
    public function attack(Kingdom $defender, array $siegeUnits, array $healers) {
        foreach ($siegeUnits as $index => $unitInfo) {
            $unitInfo = $this->attackHandler->primaryAttack($defender, $unitInfo, $healers);

            if ($unitInfo['amount'] > 0) {
                if ($unitInfo['fall_back'] === 'Buildings') {
                    $unitInfo = $this->attackHandler->attackKingdomBuildings($defender, $unitInfo, $healers);
                } else {
                    $unitInfo = $this->attackHandler->fallBackAttack($defender, $unitInfo, $healers);
                }
            }

            if ($unitInfo['amount'] > 0) {
                $unitInfo = $this->attackHandler->unitAttack($defender, $unitInfo, $healers);
            }

            $siegeUnits[$index] = $unitInfo;
        }

        return $siegeUnits;
    }
}
