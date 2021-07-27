<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcTypes;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Messages\Events\GlobalMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Flare\Events\KingdomServerMessageEvent;
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

    private $kingdomTransformer;

    private $manager;

    public function __construct(SelectedKingdom $selectedKingdom, Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->selectedKingdom    = $selectedKingdom;
        $this->kingdomTransformer = $kingdomTransformer;
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
        $defender = Kingdom::where('id', $defenderId)->where('game_map_id', $character->map->game_map_id)->first();

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

            if (!empty($unitsToSend)) {

                $totalTime = $this->fetchTotalTime($units);

                $unitMovement = UnitMovementQueue::create([
                    'character_id'    => $character->id,
                    'from_kingdom_id' => $kingdom->id,
                    'to_kingdom_id'   => $defender->id,
                    'units_moving'    => $unitsToSend,
                    'completed_at'    => now()->addMinutes($totalTime),
                    'started_at'      => now(),
                    'moving_to_x'     => $defender->x_position,
                    'moving_to_y'     => $defender->y_position,
                    'from_x'          => $kingdom->x_position,
                    'from_y'          => $kingdom->y_position,
                    'is_attacking'    => true,
                ]);

                MoveUnits::dispatch($unitMovement->id, $defenderId, 'attack', $character)->delay(now()->addMinutes($totalTime));

                $kingdom = new Item($kingdom->refresh(), $this->kingdomTransformer);

                $kingdom = $this->manager->createData($kingdom)->toArray();

                event(new UpdateKingdom($character->user, $kingdom));

                event(new UpdateUnitMovementLogs($character));
            }
        }

        $this->alertDefenderToAttack($defender);

        $this->globalAttackMessage($defender, $character);

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

    protected function globalAttackMessage(Kingdom $defender, Character $character) {
        $defenderCharacterName = null;

        if (is_null($defender->character_id)) {
            $defenderCharacterName = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name . ' (NPC)';
        } else {
            $defenderCharacterName = $defender->character->name;
        }

        $mapName = $defender->gameMap->name;
        $message = $character->name . ' Has launched an attack against: ' . $defenderCharacterName . ' on the ' . $mapName . ' plane.';

        broadcast(new GlobalMessageEvent($message));
    }

    protected function alertDefenderToAttack(Kingdom $defender) {
        if (is_null($defender->character_id)) {
            return;
        }

        $mapName = $defender->gameMap->name;
        $user    = $defender->character->user;

        $message = 'Your kingdom at: (X/Y) ' . $defender->x_position . '/' . $defender->y_position . ' ('.$mapName.') is under attack!';

        if (UserOnlineValue::isOnline($user)) {
            event(new KingdomServerMessageEvent($user, 'under-attack', $message));
        } else if ($user->kingdom_attack_email) {
            \Mail::to($user->email)->send((new GenericMail($user, $message, 'Kingdom under attack!')));
        }
    }
}
