<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\User;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Handlers\KingdomHandler;
use App\Game\Kingdoms\Handlers\UnitHandler;
use App\Game\Kingdoms\Handlers\SiegeHandler;
use App\Game\Kingdoms\Jobs\MoveUnits;

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
     * @var KingdomHandler $kingdomHandler
     */
    private $kingdomHandler;

    /**
     * AttackService constructor.
     *
     * @param SiegeHandler $siegeHandler
     * @param UnitHandler $unitHandler
     * @param KingdomHandler $kingdomHandler
     */
    public function __construct(SiegeHandler $siegeHandler, UnitHandler $unitHandler, KingdomHandler $kingdomHandler) {
        $this->siegeHandler   = $siegeHandler;
        $this->unitHandler    = $unitHandler;
        $this->kingdomHandler = $kingdomHandler;
    }

    /**
     * Handles the actual attack.
     *
     * @param UnitMovementQueue $unitMovement
     * @param int $defenderId
     */
    public function attack(UnitMovementQueue $unitMovement, Character $character, int $defenderId) {
        $attackingUnits = $unitMovement->units_moving;
        $defender       = Kingdom::where('id', $defenderId)
                                 ->where('x_position', $unitMovement->moving_to_x)
                                 ->where('y_position', $unitMovement->moving_to_y)
                                 ->first();

        $siegeUnits   = $this->fetchSiegeUnits($attackingUnits);
        $regularUnits = $this->getRegularUnits($attackingUnits);

        $newSiegeUnits   = [];
        $newRegularUnits = [];

        if (!empty($siegeUnits)) {
            $healers         = $this->fetchHealers($attackingUnits);
            $newSiegeUnits   = $this->siegeHandler->attack($defender, $siegeUnits, $healers);
        }

        if (!empty($regularUnits)) {
            $newRegularUnits = $this->unitHandler->attack($defender, $regularUnits);
        }

        $defender = $this->kingdomHandler->setKingdom($defender->refresh())->decreaseMorale()->getKingdom();

        $settlerUnit = $this->findSettlerUnit($regularUnits);

        $unitsSent      = array_merge($regularUnits, $siegeUnits);
        $survivingUnits = array_merge($newRegularUnits, $newSiegeUnits);

        if (!is_null($settlerUnit)) {
            $settlerUnit = GameUnit::find($settlerUnit['unit_id']);

            if (!is_null($settlerUnit)) {
                if ($this->isSettlerTheOnlyUnitLeft($newRegularUnits)) {
                    return $this->lostAttack($defender, $unitMovement, $character->user, $unitsSent, $survivingUnits);
                } else {
                    return $this->attemptToSettleKingdom($defender, $settlerUnit, $unitMovement, $character, $unitsSent, $survivingUnits);
                }
            }
        }

        $this->returnUnits($survivingUnits, $unitsSent, $defender, $unitMovement, $character);
    }

    protected function returnUnits(array $newUnits, array $oldUnits, Kingdom $defender, UnitMovementQueue $unitMovement, Character $character) {
        $timeToReturn = $this->getTotalReturnTime($newUnits);

        if ($timeToReturn > 0) {
            $timeToReturn = now()->addMinutes($timeToReturn);

            $unitMovement->update([
                'units_moving' => [
                    'new_units' => $newUnits,
                    'old_units' => $oldUnits
                ],
                'completed_at' => $timeToReturn,
                'started_at' => now(),
                'moving_to_x' => $unitMovement->from_x,
                'moving_to_y' => $unitMovement->from_y,
                'from_x' => $unitMovement->moving_to_x,
                'from_y' => $unitMovement->moving_to_y,
            ]);

            $this->attacked($defender, $unitMovement, $character->user, $oldUnits, $newUnits);

            $unitMovement = $unitMovement->refresh();

            MoveUnits::dispatch($unitMovement->id, $defender->id, 'return')->delay(now()->addMinutes($timeToReturn));
        } else {
            $this->lostAttack($defender, $unitMovement, $character->user, $oldUnits, $newUnits);
        }
    }

    protected function lostAttack(
        Kingdom $defender, UnitMovementQueue $unitMovement, User $user, array $unitsSent, array $newUnitsSent
    ) {
        KingdomLog::create([
            'from_kingdom_id'   => $defender->id,
            'to_kingdom_id'     => $unitMovement->to_kingdom->id,
            'status'            => KingdomLogStatusValue::LOST,
            'units_sent'        => $unitsSent,
            'units_survived'    => $newUnitsSent
        ]);

        $message = 'You lost all your units when attacking kingdom at: (X/Y) ' .
            $defender->x_position . '/' . $defender->y_position .
            ' Check the kingdom attack logs for more info.';

        event(new KingdomServerMessageEvent($user, 'all-units-lost', $message));
    }

    protected function attacked(
        Kingdom $defender, UnitMovementQueue $unitMovement, User $user, array $unitsSent, array $newUnitsSent
    ) {
        KingdomLog::create([
            'from_kingdom_id'   => $defender->id,
            'to_kingdom_id'     => $unitMovement->to_kingdom->id,
            'status'            => KingdomLogStatusValue::ATTACKED,
            'units_sent'        => $unitsSent,
            'units_survived'    => $newUnitsSent
        ]);

        $message = 'Your landed for  kingdom at: (X/Y) ' .
            $defender->x_position . '/' . $defender->y_position .
            ' And is returning. Check the kingdom attack logs for more info.';

        event(new KingdomServerMessageEvent($user, 'all-units-lost', $message));
    }

    protected function attemptToSettleKingdom(
        Kingdom $defender, GameUnit $settlerUnit, UnitMovementQueue $unitMovement, Character $character, array $oldUnits, array $newUnits
    ) {
        if ($defender->current_morale > 0) {
            $defender = $this->kingdomHandler->updateDefendersMorale($defender, $settlerUnit);

            if ($defender->current_morale === 0 || $defender->current_morale === 0.0) {

                $this->kingdomHandler->takeKingdom($unitMovement, $character, $newUnits);


                // Show Kingdom ownership message.
                // Show kingdom has fallen message.

                return;
            } else {
                $this->returnUnits($newUnits, $oldUnits, $defender, $unitMovement, $character);
            }
        } else {
            $this->kingdomHandler->takeKingdom($unitMovement, $character, $newUnits);

            // Show Kingdom ownership message.
            // Show kingdom has fallen message.

            return;
        }
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
                    'amount'           => $unitInfo['amount'],
                    'total_attack'     => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'    => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target'   => $gameUnit->primary_target,
                    'fall_back'        => $gameUnit->fall_back,
                    'unit_id'          => $gameUnit->id,
                    'healer'           => $gameUnit->can_heal,
                    'heal_for'         => !is_null($gameUnit->heal_percentage) ? $gameUnit->heal_percentage * $unitInfo['amount'] : 0,
                    'can_be_healed'    => !$gameUnit->can_not_be_healed,
                    'settler'          => $gameUnit->is_settler,
                    'time_to_return'   => $unitInfo['time_to_return'],
                ];
            }
        }

        return $regularUnits;
    }

    public function fetchHealers(array $attackingUnits): array {
        $healerUnits = [];

        foreach ($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('can_heal', true)->first();

            if (is_null($gameUnit)) {
                continue;
            }

            $healerUnits[] = [
                'amount'   => $unitInfo['amount'],
                'heal_for' => $gameUnit->heal_percentage * $unitInfo['amount'],
                'unit_id'  => $gameUnit->id,
            ];
        }

        return $healerUnits;
    }

    protected function isSettlerTheOnlyUnitLeft(array $attackingUnits): bool {
        $allDead = false;

        foreach ($attackingUnits as $unitInfo) {
            if (!$unitInfo['settler']) {
                if ($unitInfo['amount'] === 0.0 || $unitInfo['amount'] === 0) {
                    $allDead = true;
                } else {
                    $allDead = false;
                }
            }
        }

        return $allDead;
    }

    protected function getTotalReturnTime(array $units) {
        return $this->getTime($units);
    }

    protected function findSettlerUnit(array $regularUnits) {
        if (empty($regularUnits)) {
            return null;
        }

        // If there is only one unit and it's a setler
        // Then it dies.
        if (count($regularUnits) === 1) {
            return null;
        }

        $settler = null;

        foreach ($regularUnits as $unitInfo) {
            if ($unitInfo['settler']) {
                $settler = $unitInfo;
            }
        }

        return $settler;
    }

    private function getTime(array $units) {
        $time = 0;

        foreach ($units as $unitInfo) {
            if ($unitInfo['amount'] > 0) {
                $time += $unitInfo['time_to_return'];
            }
        }

        return $time;
    }
}
