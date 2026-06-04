<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitRecruitments;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Validation\KingdomUnitResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Exception;
use Illuminate\Support\Facades\Log;

class CapitalCityUnitManagementRequestHandler
{
    use ResponseBuilder;

    public function __construct(
        private readonly UnitMovementService $unitMovementService,
        private readonly UnitService $unitService,
        private readonly KingdomUnitResourceValidation $kingdomUnitResourceValidation,
        private readonly UpdateKingdom $updateKingdom,
    ) {}

    /**
     * Create unit requests for a character in a kingdom.
     *
     * @throws Exception
     */
    public function createUnitRequests(Character $character, Kingdom $kingdom, array $requestData): array
    {
        collect($requestData)->each(function ($data) use ($character, $kingdom) {
            $toKingdom = $this->findTargetKingdom($character, $data['kingdom_id']);
            $time = $this->determineTime($character, $toKingdom, $kingdom);
            $queueData = $this->prepareQueueData($character, $kingdom, $data, $time);
            $unitRequests = $this->prepareUnitRequests($data['unit_requests'], $toKingdom);

            $queueData['unit_request_data'] = $unitRequests;

            $queue = CapitalCityUnitQueue::create($queueData);

            Log::channel('capital_city_unit_recruitments')->info('Triggering events for createUnitRequests', [
                '$queue' => $queue,
                '$character' => $character->id,
                '$kingdom' => $kingdom->id,
            ]);

            $this->triggerEvents($queue, $character, $kingdom, $time);
            $this->updateKingdomStatus($kingdom);
        });

        return $this->successResult([
            'message' => 'Units requests have been queued up and sent off. If you close this modal you should now see
        a Unit Queue tab which will show you the progress of your requests. Kingdom logs will be generated
        foreach kingdom to details what was or was not recruited.',
        ]);
    }

    /**
     * Find the target kingdom for the request.
     */
    private function findTargetKingdom(Character $character, int $kingdomId): ?Kingdom
    {
        return $character->kingdoms->find($kingdomId);
    }

    /**
     * Determine the time required for unit movement.
     */
    private function determineTime(Character $character, ?Kingdom $toKingdom, Kingdom $fromKingdom): int
    {
        return $this->unitMovementService->determineTimeRequired(
            $character,
            $toKingdom,
            $fromKingdom->id,
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION
        );
    }

    /**
     * Prepare the data for the unit queue.
     */
    private function prepareQueueData(Character $character, Kingdom $kingdom, array $data, int $time): array
    {
        $minutes = now()->addMinutes($time);

        return [
            'requested_kingdom' => $kingdom->id,
            'character_id' => $character->id,
            'kingdom_id' => $data['kingdom_id'],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'messages' => [],
            'started_at' => now(),
            'completed_at' => $minutes,
        ];
    }

    /**
     * Prepare the unit requests data.
     */
    private function prepareUnitRequests(array $unitRequestsData, ?Kingdom $toKingdom): array
    {
        return collect($unitRequestsData)->filter(function ($unitRequest) use ($toKingdom) {
            $unit = GameUnit::where('name', $unitRequest['unit_name'])->first();

            if (is_null($unit) || is_null($toKingdom)) {
                return false;
            }

            return ! $this->hasActiveManualUnitQueue($toKingdom, $unit) &&
                ! $this->hasActiveCapitalCityUnitQueue($toKingdom, $unit);
        })->map(function ($unitRequest) use ($toKingdom) {
            $unit = GameUnit::where('name', $unitRequest['unit_name'])->first();

            return [
                'name' => $unitRequest['unit_name'],
                'amount' => $unitRequest['unit_amount'],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'costs' => $this->kingdomUnitResourceValidation->getCostsRequired($toKingdom, $unit, $unitRequest['unit_amount']),
            ];
        })->toArray();
    }

    private function hasActiveManualUnitQueue(Kingdom $kingdom, GameUnit $unit): bool
    {
        return UnitInQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->where('game_unit_id', $unit->id)
            ->where('completed_at', '>', now())
            ->exists();
    }

    private function hasActiveCapitalCityUnitQueue(Kingdom $kingdom, GameUnit $unit): bool
    {
        return CapitalCityUnitQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->contains(function (CapitalCityUnitQueue $queue) use ($unit) {
                return collect($queue->unit_request_data)
                    ->reject(function (array $request) {
                        return in_array($request['secondary_status'] ?? null, [
                            CapitalCityQueueStatus::FINISHED,
                            CapitalCityQueueStatus::REJECTED,
                            CapitalCityQueueStatus::CANCELLED,
                            CapitalCityQueueStatus::CANCELLATION_REJECTED,
                        ], true);
                    })
                    ->contains(fn (array $request) => ($request['name'] ?? null) === $unit->name);
            });
    }

    /**
     * Trigger events and dispatch jobs related to the unit queue.
     */
    private function triggerEvents(CapitalCityUnitQueue $queue, Character $character, Kingdom $kingdom, int $time): void
    {
        event(new UpdateCapitalCityUnitRecruitments($character, $kingdom));
        event(new UpdateCapitalCityUnitQueueTable($character, $kingdom));

        CapitalCityUnitRequestMovement::dispatch($queue->id, $character->id)->onConnection('long_running')->onQueue('default_long')->delay(now()->addMinutes($time));
    }

    /**
     * Update the status of the kingdom.
     */
    private function updateKingdomStatus(Kingdom $kingdom): void
    {
        $this->updateKingdom->updateKingdom($kingdom);
    }
}
