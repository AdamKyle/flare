<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Kingdoms\Handlers\AttackKingdomWithUnitsHandler;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\UnitMovementQueue;

class MoveUnits implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param int $movementId
     * @param array $additionalParams
     */
    public function __construct(private readonly int $movementId, private readonly array $additionalParams = []) {}

    /**
     * @param AttackKingdomWithUnitsHandler $attackKingdomWithUnitsHandler
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    public function handle(AttackKingdomWithUnitsHandler $attackKingdomWithUnitsHandler,
                           UpdateKingdom $updateKingdom
    ): void {
        $unitMovement = UnitMovementQueue::find($this->movementId);

        if (is_null($unitMovement)) {
            return;
        }

        if (!$unitMovement->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $unitMovement->completed_at->diffInMinutes(now());


            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                MoveUnits::dispatch(
                    $this->movementId,
                    $this->additionalParams,
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        if ($unitMovement->is_moving || $unitMovement->is_returning || $unitMovement->is_recalled) {
            $this->moveUnitsFromOneKingdomToTheNext($unitMovement, $updateKingdom);

            return;
        }

        if ($unitMovement->is_attacking) {

            $kingdom          = Kingdom::find($unitMovement->to_kingdom_id);
            $attackingKingdom = Kingdom::find($unitMovement->from_kingdom_id);

            $this->cleanUpMovementQueue($unitMovement, $updateKingdom);

            $attackKingdomWithUnitsHandler->attackKingdomWithUnits($kingdom, $attackingKingdom, $unitMovement->units_moving);
        }

        if ($unitMovement->resources_requested) {
            $this->handleWhenResourceRequested($unitMovement, $updateKingdom);
        }
    }

    /**
     * Move units from one kingdom to the next.
     *
     * @param UnitMovementQueue $unitMovement
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    protected function moveUnitsFromOneKingdomToTheNext(UnitMovementQueue $unitMovement, UpdateKingdom $updateKingdom): void {
        $unitsMoving = $unitMovement->units_moving;
        $toKingdom   = Kingdom::find($unitMovement->to_kingdom_id);
        $fromKingdom = Kingdom::find($unitMovement->from_kingdom_id);

        if ($this->shouldBail($unitMovement, $toKingdom, $fromKingdom)) {
            return;
        }

        foreach ($unitsMoving as $unitMoving) {

            if ($unitMovement->is_returning || $unitMovement->is_recalled) {
                $this->returnUnitToKingdom($toKingdom, $unitMoving);
            } else {
                $foundUnit = $this->findUnitToUpdate($toKingdom, $fromKingdom, $unitMoving);

                $this->updateOrCrateUnitsForToKingdom($toKingdom, $fromKingdom, $unitMoving, $foundUnit);
            }
        }

        $unitMovement->delete();

        $character = Character::find($unitMovement->character_id);

        $updateKingdom->updateKingdomAllKingdoms($character);

        event(new UpdateKingdomQueues($toKingdom));
        event(new UpdateKingdomQueues($fromKingdom));
    }

    /**
     * Either create or update the units in the kingdom the units are moving to.
     *
     * @param Kingdom $toKingdom
     * @param Kingdom $fromKingdom
     * @param array $unitMoving
     * @param KingdomUnit|null $foundUnit
     * @return void
     */
    protected function updateOrCrateUnitsForToKingdom(Kingdom $toKingdom, Kingdom $fromKingdom, array $unitMoving, ?KingdomUnit $foundUnit = null): void {
        if (is_null($foundUnit)) {
            $toKingdom->units()->create([
                'kingdom_id'   => $toKingdom->id,
                'game_unit_id' => $fromKingdom->units()->find($unitMoving['unit_id'])->gameUnit->id,
                'amount'       => $unitMoving['amount'],
            ]);
        } else {
            $newAmount = $foundUnit->amount + $unitMoving['amount'];

            if ($newAmount > KingdomMaxValue::MAX_UNIT) {
                $newAmount = KingdomMaxValue::MAX_UNIT - $newAmount;
            }

            $foundUnit->update([
                'amount' => $newAmount,
            ]);
        }
    }

    /**
     * Find the unit that is moving to the new kingdom.
     *
     * @param Kingdom $toKingdom
     * @param Kingdom $fromKingdom
     * @param array $unitMoving
     * @return KingdomUnit|null
     */
    protected function findUnitToUpdate(Kingdom $toKingdom, Kingdom $fromKingdom, array $unitMoving): ?KingdomUnit {
        return $toKingdom->units->filter(function($unit) use($unitMoving, $fromKingdom) {

            $fromKingdomUnit = $fromKingdom->units()->find($unitMoving['unit_id']);

            if ($unit->gameUnit->name === $fromKingdomUnit->gameUnit->name) {
                return $unit;
            }
        })->first();
    }

    /**
     * Add returning units to the returning to kingdom.
     *
     * - If the unit cannot be found just bail.
     * - If the amount of the unit moving is 0, just bail.
     * - If the amount + the current amount  > max amount, just use max amount.
     *
     * @param Kingdom $toKingdom
     * @param array $unitMoving
     * @return void
     */
    protected function returnUnitToKingdom(Kingdom $toKingdom, array $unitMoving): void {
        $unit = $toKingdom->units()->find($unitMoving['unit_id']);

        if (is_null($unit) || $unitMoving['amount'] === 0) {
            return;
        }

        $newAmount = $unit->amount + $unitMoving['amount'];

        if ($newAmount > KingdomMaxValue::MAX_UNIT) {
            $newAmount = KingdomMaxValue::MAX_UNIT;
        }

        $unit->update(['amount' => $newAmount]);
    }

    /**
     * Should we bail?
     *
     * - If the kingdom is not yours anymore, we determine what to do next.
     *   - If the kingdom does not belong to you, maybe it's npc, we bail, you lost the units.
     *   - If the kingdom was taken, perhaps by war, you lost the units.
     *   - If the kingdom you are going to does not belong to you, but the kingdom you are returning to does,
     *     We return the units.
     *
     * @param UnitMovementQueue $unitMovement
     * @param Kingdom $toKingdom
     * @param Kingdom $fromKingdom
     * @return bool
     */
    protected function shouldBail(UnitMovementQueue $unitMovement, Kingdom $toKingdom, Kingdom $fromKingdom): bool {
        $user = Character::find($unitMovement->character_id)->user();

        if ($toKingdom->character_id !== $unitMovement->character_id && !$unitMovement->is_returning) {
            $attributes =  $unitMovement->getAttributes();

            if (is_null($fromKingdom->character_id)) {
                event(new ServerMessageEvent($user, 'Your units were lost in movement as the kingdom they would return to does not belong to you.'));

                return true;
            }

            if ($fromKingdom->character_id !== $unitMovement->character_id) {
                event(new ServerMessageEvent($user, 'Your units were lost in movement as the kingdom they would return to does not belong to you.'));

                return true;
            }

            $attributes['character']       = $fromKingdom->character_id;
            $attributes['to_kingdom_id']   = $attributes['from_kingdom_id'];
            $attributes['from_kingdom_id'] = $attributes['to_kingdom_id'];

            $unitMovementQueue = UnitMovementQueue::create($attributes);

            event(new UpdateKingdomQueues($toKingdom));
            event(new UpdateKingdomQueues($fromKingdom));

            $minutes = (new Carbon($attributes['completed_at']))->diffInMinutes($attributes['started_at']);

            MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);

            event(new ServerMessageEvent($user, 'Your units are returning. The kingdom you sent them to does not belong to you anymore.'));

            $unitMovement->delete();

            return true;
        }

        if ($toKingdom->character_id !== $unitMovement->character_id && $unitMovement->is_returning) {
            event(new ServerMessageEvent($user, 'Your units were lost in movement as the kingdom they would return to does not belong to you.'));
        }

        return false;
    }

    private function handleWhenResourceRequested(UnitMovementQueue $unitMovementQueue, UpdateKingdom $updateKingdom): void {

        if (empty($this->additionalParams)) {
            $unitMovementQueue->delete();

            return;
        }

        if (!isset($this->additionalParams['amount_of_resources'])) {
            $unitMovementQueue->delete();

            return;
        }

        if (!isset($this->additionalParams['additional_log_messages'])) {
            $unitMovementQueue->delete();

            return;
        }

        RequestResources::dispatch($unitMovementQueue->character_id, $unitMovementQueue->to_kingdom_id, $unitMovementQueue->from_kingdom_id, $this->additionalParams['amount_of_resources'], $unitMovementQueue->units_moving, $this->additionalParams['additional_log_messages']);

        $this->cleanUpMovementQueue($unitMovementQueue, $updateKingdom);
    }

    private function cleanUpMovementQueue(UnitMovementQueue $unitMovement, UpdateKingdom $updateKingdom): void {
        $kingdom          = Kingdom::find($unitMovement->to_kingdom_id);
        $secondaryKingdom = Kingdom::find($unitMovement->from_kingdom_id);

        $unitMovement->delete();

        event(new UpdateKingdomQueues($kingdom));
        event(new UpdateKingdomQueues($secondaryKingdom));

        if (!is_null($kingdom->character_id) && $kingdom->character_id !== $secondaryKingdom->character_id) {
            $updateKingdom->updateKingdomAllKingdoms($kingdom->character);
        }

        $updateKingdom->updateKingdomAllKingdoms($secondaryKingdom->character);
    }
}
