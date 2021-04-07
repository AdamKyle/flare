<?php

namespace App\Game\Kingdoms\Service;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Transformers\SelectedKingdom;

class KingdomsAttackService {

    use ResponseBuilder;

    private $selectedKingdom;

    private $kingdomTransfromer;

    private $manager;

    public function __construct(SelectedKingdom $selectedKingdom, Manager $manager, KingdomTransformer $kingdomTransfromer) {
        $this->selectedKingdom    = $selectedKingdom;
        $this->kingdomTransfromer = $kingdomTransfromer;
        $this->manager            = $manager;
    }

    public function fetchSelectedKingdomData(Character $character, array $kingdoms): array {
        $kingdomData = [];

        foreach ($kingdoms as $kingdomId) {
            $kingdom = Kingdom::where('character_id', $character->id)->where('id', $kingdomId)->first();

            if (is_null($kingdom)) {
                return $this->errorResult('You do not own this kingdom.');
            }

            $kingdom = new Item($kingdom, $this->selectedKingdom);
            $kingdom = $this->manager->createData($kingdom)->toArray();

            $kingdomData[] = $kingdom;
        }

        return $this->successResult($kingdomData);
    }

    public function attackKingdom(Character $character, int $defenderId, array $params) {
        $defender = Kingdom::find($defenderId);

        if (is_null($defender)) {
            return $this->errorResult('Defender kingdom does not exist for: ' . $defenderId);
        }

        foreach ($params as $kingdomName => $units) {
            $kingdom = Kingdom::where('character_id', $character->id)
                              ->where('name', $kingdomName)
                              ->first();

            if (is_null($kingdom)) {
                return $this->errorResult('No such kingdom for name: ' . $kingdomName);
            }

            $unitsToSend = [];

            try {
                $unitsToSend = $this->fetchUnitsToSend($kingdom, $units);
            } catch (\Exception $e) {
                return $this->errorResult($e->getMessage());
            }

            $totalTime = $this->fetchTotalTime($units);

            $timeTillFinished = now()->addMinutes($totalTime);

            $unitMovement = UnitMovementQueue::create([
                'from_kingdom_id'   => $kingdom->id,
                'to_kingdom_id'     => $defender->id,
                'units_moving'      => $unitsToSend,
                'completed_at'      => $timeTillFinished,
                'started_at'        => now(),
                'moving_to_x'       => $defender->x_position,
                'moving_to_y'       => $defender->y_position,
                'from_x'            => $kingdom->x_position,
                'from_y'            => $kingdom->y_position,
            ]);

            MoveUnits::dispatch($unitMovement->id, $defenderId, 'attack', $character)->delay(now()->addMinutes(2)/*$timeTillFinished*/);

            $kingdom  = new Item($kingdom->refresh(), $this->kingdomTransfromer);

            $kingdom  = $this->manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($character->user, $kingdom));
        }

        return $this->successResult();
    }

    protected function fetchUnitsToSend(Kingdom $kingdom, array $units) {
        $unitsToSend = [];

        foreach ($units as $unitName => $unitInformation) {
            $unit = $this->fetchGameUnit($kingdom, $unitName);

            if (is_null($unit)) {
                throw new \Exception('No unit exists for name: ' . $unitName . ' on this kingdom: ' . $kingdom->name);
            }

            $kingdomUnitInformation = $kingdom->units()->where('game_unit_id', $unit->id)->first();

            $newAmountInKingdom     = $kingdomUnitInformation->amount - $unitInformation['amount_to_send'];

            if ($newAmountInKingdom < 0) {
                throw new \Exception(
                    'You don\'t have enough units. You have: ' .
                    $kingdomUnitInformation->amount .
                    ' and are trying to send: ' .
                    $unitInformation['amount_to_send'] .
                    ' for: ' . $kingdom->name
                );
            }

            if ($unitInformation['amount_to_send'] > 0) {
                $unitsToSend[] = [
                    'unit_id'        => $unit->id,
                    'amount'         => $unitInformation['amount_to_send'],
                    'time_to_return' => $unitInformation['total_time'],
                ];

                $kingdomUnitInformation->update([
                    'amount' => $newAmountInKingdom,
                ]);
            }
        }

        return $unitsToSend;
    }

    protected function fetchGameUnit(Kingdom $kingdom, string $unitName) {
        return $kingdom->units()->select('game_units.*')->join('game_units', function($join) use($unitName) {
            $join->on('kingdom_units.game_unit_id', 'game_units.id')
                 ->where('game_units.name', $unitName);
        })->first();
    }

    protected function fetchTotalTime(array $unitsToSend): int {
        $totalTime = 0;

        foreach ($unitsToSend as $unitName => $unitInformation) {
            $totalTime += $unitInformation['total_time'];
        }

        return $totalTime;
    }
}
