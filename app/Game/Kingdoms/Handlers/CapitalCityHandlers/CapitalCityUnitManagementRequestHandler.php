<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Validation\KingdomUnitResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Exception;


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
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
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
     *
     * @param Character $character
     * @param int $kingdomId
     * @return Kingdom|null
     */
    private function findTargetKingdom(Character $character, int $kingdomId): ?Kingdom
    {
        return $character->kingdoms->find($kingdomId);
    }

    /**
     * Determine the time required for unit movement.
     *
     * @param Character $character
     * @param Kingdom|null $toKingdom
     * @param Kingdom $fromKingdom
     * @return int
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
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $data
     * @param int $time
     * @return array
     */
    private function prepareQueueData(Character $character, Kingdom $kingdom, array $data, int $time): array
    {
        $minutes = now()->addMinutes($time);

        return [
            'requested_kingdom' => $kingdom->id,
            'character_id' => $character->id,
            'kingdom_id' => $data['kingdom_id'],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'messages' => null,
            'started_at' => now(),
            'completed_at' => $minutes,
        ];
    }

    /**
     * Prepare the unit requests data.
     *
     * @param array $unitRequestsData
     * @param Kingdom|null $toKingdom
     * @return array
     */
    private function prepareUnitRequests(array $unitRequestsData, ?Kingdom $toKingdom): array
    {
        return collect($unitRequestsData)->map(function ($unitRequest) use ($toKingdom) {
            $unit = GameUnit::where('name', $unitRequest['unit_name'])->first();

            return [
                'name' => $unitRequest['unit_name'],
                'amount' => $unitRequest['unit_amount'],
                'secondary_status' => null,
                'costs' => $this->kingdomUnitResourceValidation->getCostsRequired($toKingdom, $unit, $unitRequest['unit_amount']),
            ];
        })->toArray();
    }

    /**
     * Trigger events and dispatch jobs related to the unit queue.
     *
     * @param CapitalCityUnitQueue $queue
     * @param Character $character
     * @param Kingdom $kingdom
     * @param int $time
     * @return void
     */
    private function triggerEvents(CapitalCityUnitQueue $queue, Character $character, Kingdom $kingdom, int $time): void
    {
        event(new UpdateCapitalCityUnitQueueTable($character, $kingdom));

        CapitalCityUnitRequestMovement::dispatch($queue->id, $character->id)->delay(now()->addMinutes($time));
    }

    /**
     * Update the status of the kingdom.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    private function updateKingdomStatus(Kingdom $kingdom): void
    {
        $this->updateKingdom->updateKingdom($kingdom);
    }
}
