<?php

namespace App\Game\Kingdoms\Service;

use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Transformers\SelectedKingdom;

class KingdomsAttackService {

    use ResponseBuilder;

    /**
     * @var SelectedKingdom $selectedKingdom
     */
    private SelectedKingdom $selectedKingdom;

    /**
     * @var KingdomTransformer $kingdomTransformer
     */
    private KingdomTransformer $kingdomTransformer;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param SelectedKingdom $selectedKingdom
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     */
    public function __construct(SelectedKingdom $selectedKingdom, Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->selectedKingdom    = $selectedKingdom;
        $this->kingdomTransformer = $kingdomTransformer;
        $this->manager            = $manager;
    }

    /**
     * Fetches the selected kingdoms' data.
     *
     * @param Character $character
     * @param array $kingdoms
     * @return array
     */
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

    /**
     * Launch an attack against a kingdom.
     *
     * @param Character $character
     * @param int $defenderId
     * @param array $params
     * @return array
     */
    public function attackKingdom(Character $character, int $defenderId, array $params) {
        $defender = Kingdom::where('id', $defenderId)->where('game_map_id', $character->map->game_map_id)->first();

        if (is_null($defender)) {
            return $this->errorResult('Defender kingdom does not exist for: ' . $defenderId);
        }

        $timeReductionSkill = $character->skills->filter(function($skill) {
            return $skill->type()->effectsKingdom();
        })->first();

        foreach ($params as $kingdomName => $units) {
            $kingdom = Kingdom::where('character_id', $character->id)
                              ->where('name', $kingdomName)
                              ->first();

            if (is_null($kingdom)) {
                return $this->errorResult('No such kingdom for name: ' . $kingdomName);
            }

            try {
                $unitsToSend = $this->fetchUnitsToSend($kingdom, $units);
            } catch (\Exception $e) {
                return $this->errorResult($e->getMessage());
            }

            if (!empty($unitsToSend)) {

                $totalTime = $this->fetchTotalTime($units);

                $totalTime = $totalTime - $totalTime * $timeReductionSkill->unit_movement_time_reduction;

                if ($totalTime <= 0.0) {
                    $totalTime = 1;
                }

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

                $timeForDispatch = $totalTime;

                // @codeCoverageIgnoreStart
                if ($totalTime > 15) {
                    $timeForDispatch = 15;
                }
                // @codeCoverageIgnoreEnd

                MoveUnits::dispatch($unitMovement->id, $defenderId, 'attack', $character, $timeForDispatch)->delay(now()->addMinutes($timeForDispatch));

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

    /**
     * Fetches the units to send.
     *
     * Can throw an exception if the unit doesn't exist on the kingdom.
     *
     * Can also throw if you do not have enough units in the kingdom.
     *
     * Will also update the kingdoms units with the reamining values.
     *
     * @param Kingdom $kingdom
     * @param array $units
     * @return array
     * @throws \Exception
     */
    protected function fetchUnitsToSend(Kingdom $kingdom, array $units) {
        $unitsToSend = [];

        foreach ($units as $unitName => $unitInformation) {
            $unit = $this->fetchGameUnit($kingdom, $unitName);

            if (is_null($unit)) {
                throw new \Exception('No unit exists for name: ' . $unitName . ' on this kingdom: ' . $kingdom->name);
            }

            $newAmountInKingdom  = $unit->amount - $unitInformation['amount_to_send'];

            if ($newAmountInKingdom < 0) {
                throw new \Exception(
                    'You don\'t have enough units. You have: ' .
                    $unit->amount .
                    ' and are trying to send: ' .
                    $unitInformation['amount_to_send'] .
                    ' for: ' . $kingdom->name
                );
            }

            if ($unitInformation['amount_to_send'] > 0) {
                $unitsToSend[] = [
                    'unit_id'        => $unit->game_unit_id,
                    'amount'         => $unitInformation['amount_to_send'],
                    'time_to_return' => $unitInformation['total_time'],
                ];

                $unit->update([
                    'amount' => $newAmountInKingdom,
                ]);
            }
        }

        return $unitsToSend;
    }

    /**
     * Fetches the unit off the kingdom.
     * @param Kingdom $kingdom
     * @param string $unitName
     * @return KingdomUnit|null
     */
    protected function fetchGameUnit(Kingdom $kingdom, string $unitName): ?KingdomUnit {
        $gameUnit = GameUnit::where('name', $unitName)->first();

        if (is_null($gameUnit)) {
            return null;
        }

        $unit = $kingdom->units->where('game_unit_id', $gameUnit->id)->first();

        Log::info($unit);
        Log::info($unitName);

        return $kingdom->units->where('game_unit_id', $gameUnit->id)->first();
    }

    /**
     * Fetches the total movement time.
     *
     * @param array $unitsToSend
     * @return int
     */
    protected function fetchTotalTime(array $unitsToSend): int {
        $totalTime = 0;

        foreach ($unitsToSend as $unitName => $unitInformation) {
            $totalTime += $unitInformation['total_time'];
        }

        return $totalTime;
    }

    /**
     * Sends out a global attack message.
     *
     * @param Kingdom $defender
     * @param Character $character
     */
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

    /**
     * Alerts the defender an attack is in coming.
     *
     * @param Kingdom $defender
     */
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
