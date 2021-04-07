<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
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
    public function __construct(SiegeHandler $siegeHandler, UnitHandler $unitHandler, KingdomResourcesService $kingdomResourcesService) {
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

        $this->kingdomResourcesService->setKingdom($defender->refresh())->increaseOrDecreaseMorale();

        $defender = $defender->refresh();

        $settlerUnit = $this->findSettlerUnit($regularUnits);

        if (!is_null($settlerUnit)) {
            $settlerUnit = GameUnit::find($settlerUnit['unit_id']);

            if (!is_null($settlerUnit)) {
                if (count($regularUnits) === 1) {
                    $regularUnits = [];
                } else {
                    if ($defender->current_morale > 0) {
                        $currentMorale = $defender->current_morale - $settlerUnit->reduces_morale_by;

                        $defender->current_morale = $currentMorale < 0 ? 0 : $currentMorale;

                        $defender->save();

                        $defender = $defender->refresh();

                        if ($defender->current_morale === 0) {
                            $kingdom = $unitMovement->toKingdom;

                            $kingdom->update([
                                'character_id' => $character->id,
                            ]);

                            foreach ($regularUnits as $unitInfo) {
                                if (!$unitInfo['settler']) {
                                    $unit = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

                                    if (!is_null($unit)) {
                                        $unit->update([
                                            'amount' => $unit->amount + $unitInfo['amount']
                                        ]);
                                    }
                                }
                            }
                            dump($kingdom->refresh()->load('units'));
                            // Show Kingdom ownership message.
                            // Show kingdom has fallen message.

                            return;
                        }
                    } else {
                        $kingdom = $unitMovement->toKingdom;

                        $kingdom->update([
                            'character_id' => $character->id,
                        ]);

                        foreach ($regularUnits as $unitInfo) {
                            if (!$unitInfo['settler']) {
                                $unit = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

                                if (!is_null($unit)) {
                                    $unit->update([
                                        'amount' => $unit->amount + $unitInfo['amount']
                                    ]);
                                }
                            }
                        }

                        dump($kingdom->refresh()->load('units'));

                        // Show Kingdom ownership message.
                        // Show kingdom has fallen message.

                        return;
                    }
                }
            }
        }

        $timeToReturn = $this->getTotalReturnTime($newSiegeUnits, $newRegularUnits);

        if ($timeToReturn > 0) {
            $timeToReturn = now()->addMinutes($timeToReturn);

            $unitMovement->update([
                'units_moving' => [
                    'new_units' => array_merge($newSiegeUnits, $newRegularUnits),
                    'old_units' => array_merge($siegeUnits, $regularUnits)
                ],
                'completed_at' => $timeToReturn,
                'started_at' => now(),
                'moving_to_x' => $unitMovement->from_x,
                'moving_to_y' => $unitMovement->from_y,
                'from_x' => $unitMovement->moving_to_x,
                'from_y' => $unitMovement->moving_to_y,
            ]);

            $unitMovement = $unitMovement->refresh();

            MoveUnits::dispatch($unitMovement->id, $defenderId, 'return')->delay(now()->addMinutes(2)/*$timeToReturn*/);
        } else {
            dump('All units lost ...');
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

    protected function getTotalReturnTime(array $regularUnits, array $siegeUnits) {
        $time = 0;

        if (!empty($regularUnits)) {
            $time += $this->getTime($regularUnits);
        }

        if (!empty($siegeUnits)) {
            $time += $this->getTime($siegeUnits);
        }

        return $time;
    }

    protected function findSettlerUnit(array $regularUnits) {
        if (empty($regularUnits)) {
            return null;
        }

        // If there is only one unit and it's a setler
        // Then it dies.
        if (count($regularUnits) === 1) {
            return null;
        } else {
            foreach ($regularUnits as $unitInfo) {
                if ($unitInfo['settler']) {
                    return $unitInfo;
                }
            }
        }

        return null;
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
