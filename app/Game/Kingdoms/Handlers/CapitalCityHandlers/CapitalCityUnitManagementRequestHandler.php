<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueRequest;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitRecruitments;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Validation\KingdomUnitResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Exception;
use Illuminate\Support\Collection;
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
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     * @throws Exception
     */
    public function createUnitRequests(Character $character, Kingdom $kingdom, array $requestData): array
    {
        $character->loadMissing(['kingdoms', 'passiveSkills.passiveSkill']);
        $gameUnitsByName = GameUnit::query()->get()->keyBy('name');
        $targetKingdoms = $this->getTargetKingdoms($character, $requestData);
        $validationMessage = $this->validateRequestData($requestData, $targetKingdoms, $gameUnitsByName);

        if (! is_null($validationMessage)) {
            return $this->errorResult($validationMessage);
        }

        $createdQueues = [];
        $groupedRequestData = $this->groupRequestDataByKingdom($requestData);

        collect($groupedRequestData)->each(function ($data) use ($character, $kingdom, $gameUnitsByName, $targetKingdoms, &$createdQueues) {
            $toKingdom = $targetKingdoms->get($data['kingdom_id']);

            if (is_null($toKingdom)) {
                return;
            }

            $time = $this->determineTime($character, $toKingdom, $kingdom);
            $queueData = $this->prepareQueueData($character, $kingdom, $data, $time);

            $unitRequests = $this->prepareUnitRequests($data['unit_requests'], $toKingdom, $gameUnitsByName);

            if (empty($unitRequests)) {
                return;
            }

            $queueData['unit_request_data'] = $unitRequests;

            $queue = CapitalCityUnitQueue::create($queueData);

            Log::channel('capital_city_unit_recruitments')->info('Created unit queue entry for createUnitRequests', [
                '$queue' => $queue,
                '$character' => $character->id,
                '$kingdom' => $kingdom->id,
            ]);

            CapitalCityUnitRequestMovement::dispatch($queue->id, $character->id)
                ->onConnection('long_running')
                ->onQueue('default_long')
                ->delay(now()->addMinutes($time));

            $createdQueues[] = $queue;

            event(new UpdateCapitalCityUnitQueueRequest(
                $character->user_id,
                false,
                $toKingdom->name . ' processed.',
                'progress',
                $toKingdom->id,
                $toKingdom->name,
                $this->formatQueueData($queue),
                collect($unitRequests)->pluck('name')->values()->toArray()
            ));
        });

        if (! empty($createdQueues)) {
            $this->updateKingdomStatus($kingdom);
            event(new UpdateCapitalCityUnitRecruitments($character, $kingdom));
            event(new UpdateCapitalCityUnitQueueTable($character, $kingdom));
        }

        return $this->successResult([
            'message' => 'Units requests have been queued up and sent off. If you close this modal you should now see
        a Unit Queue tab which will show you the progress of your requests. Kingdom logs will be generated
        foreach kingdom to details what was or was not recruited.',
        ]);
    }

    private function groupRequestDataByKingdom(array $requestData): array
    {
        $groupedRequestData = [];

        foreach ($requestData as $request) {
            if (! is_array($request) || ! array_key_exists('kingdom_id', $request)) {
                continue;
            }

            $kingdomId = (int) $request['kingdom_id'];

            if (! isset($groupedRequestData[$kingdomId])) {
                $groupedRequestData[$kingdomId] = [
                    'kingdom_id' => $kingdomId,
                    'unit_requests' => [],
                ];
            }

            foreach ($request['unit_requests'] ?? [] as $unitRequest) {
                $groupedRequestData[$kingdomId]['unit_requests'][] = $unitRequest;
            }
        }

        return array_values($groupedRequestData);
    }

    private function formatQueueData(CapitalCityUnitQueue $queue): array
    {
        $currentTime = now();
        $kingdom = $queue->kingdom;
        $totalTime = 0;

        if ($this->hasActiveTimer($queue->status) && $queue->completed_at->greaterThan($currentTime)) {
            $totalTime = $currentTime->diffInSeconds($queue->completed_at);
        }

        return [
            'queue_id' => $queue->id,
            'queue_ids' => [$queue->id],
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'map_name' => $kingdom->gameMap->name,
            'unit_requests' => collect($queue->unit_request_data)->map(function (array $request) use ($queue) {
                return [
                    'queue_id' => $queue->id,
                    'unit_name' => $request['name'],
                    'secondary_status' => $request['secondary_status'],
                    'amount_to_recruit' => $request['amount'],
                ];
            })->toArray(),
            'status' => $queue->status,
            'total_time' => $totalTime,
            'time_remaining' => $totalTime,
            'timer_duration' => (int) max(0, $queue->started_at->diffInSeconds($queue->completed_at)),
            'timer_started_at' => $queue->started_at->timestamp * 1000,
            'started_at' => $queue->started_at->toIso8601String(),
            'completed_at' => $queue->completed_at->toIso8601String(),
            'completed_at_timestamp' => $queue->completed_at->timestamp * 1000,
            'phase_timer_label' => $this->phaseTimerLabel($queue->status),
        ];
    }

    private function hasActiveTimer(string $status): bool
    {
        return in_array($status, [
            CapitalCityQueueStatus::TRAVELING,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::RECRUITING,
        ], true);
    }

    private function phaseTimerLabel(string $status): string
    {
        return match ($status) {
            CapitalCityQueueStatus::TRAVELING => 'Traveling',
            CapitalCityQueueStatus::REQUESTING => 'Requesting Resources',
            CapitalCityQueueStatus::RECRUITING => 'Recruiting',
            CapitalCityQueueStatus::PROCESSING => 'Processing',
            default => 'Processing',
        };
    }

    /**
     * Find the target kingdom for the request.
     *
     * @param Character $character
     * @param int $kingdomId
     * @return Kingdom|null
     */
    private function getTargetKingdoms(Character $character, array $requestData): Collection
    {
        $targetKingdomIds = collect($requestData)
            ->pluck('kingdom_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($targetKingdomIds)) {
            return collect();
        }

        return $character->kingdoms()
            ->whereIn('id', $targetKingdomIds)
            ->get()
            ->keyBy('id');
    }

    private function validateRequestData(array $requestData, Collection $targetKingdoms, Collection $gameUnitsByName): ?string
    {
        $targetKingdomIds = $targetKingdoms->keys()->values()->all();

        if (empty($targetKingdomIds)) {
            return null;
        }

        $ownedUnitAmountMap = [];
        KingdomUnit::whereIn('kingdom_id', $targetKingdomIds)
            ->get(['kingdom_id', 'game_unit_id', 'amount'])
            ->each(function (KingdomUnit $kingdomUnit) use (&$ownedUnitAmountMap) {
                $ownedUnitAmountMap[$kingdomUnit->kingdom_id][$kingdomUnit->game_unit_id] =
                    ($ownedUnitAmountMap[$kingdomUnit->kingdom_id][$kingdomUnit->game_unit_id] ?? 0) + $kingdomUnit->amount;
            });

        $manualQueueAmountMap = [];
        UnitInQueue::whereIn('kingdom_id', $targetKingdomIds)
            ->where('completed_at', '>', now())
            ->get(['kingdom_id', 'game_unit_id', 'amount'])
            ->each(function (UnitInQueue $unitInQueue) use (&$manualQueueAmountMap) {
                $manualQueueAmountMap[$unitInQueue->kingdom_id][$unitInQueue->game_unit_id] =
                    ($manualQueueAmountMap[$unitInQueue->kingdom_id][$unitInQueue->game_unit_id] ?? 0) + $unitInQueue->amount;
            });

        $capitalCityQueueAmountMap = [];
        CapitalCityUnitQueue::whereIn('kingdom_id', $targetKingdomIds)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get(['kingdom_id', 'unit_request_data'])
            ->each(function (CapitalCityUnitQueue $queue) use (&$capitalCityQueueAmountMap, $gameUnitsByName) {
                foreach ($queue->unit_request_data as $request) {
                    if (in_array($request['secondary_status'] ?? null, [
                        CapitalCityQueueStatus::FINISHED,
                        CapitalCityQueueStatus::REJECTED,
                        CapitalCityQueueStatus::CANCELLED,
                        CapitalCityQueueStatus::CANCELLATION_REJECTED,
                    ], true)) {
                        continue;
                    }

                    $gameUnit = $gameUnitsByName->get($request['name'] ?? null);

                    if (is_null($gameUnit)) {
                        continue;
                    }

                    $capitalCityQueueAmountMap[$queue->kingdom_id][$gameUnit->id] =
                        ($capitalCityQueueAmountMap[$queue->kingdom_id][$gameUnit->id] ?? 0) + (int) ($request['amount'] ?? 0);
                }
            });

        $requestedAmounts = [];

        foreach ($requestData as $kingdomRequestData) {
            $targetKingdom = $targetKingdoms->get($kingdomRequestData['kingdom_id'] ?? null);

            if (is_null($targetKingdom)) {
                continue;
            }

            foreach ($kingdomRequestData['unit_requests'] ?? [] as $unitRequest) {
                $gameUnit = $gameUnitsByName->get($unitRequest['unit_name'] ?? null);

                if (is_null($gameUnit)) {
                    continue;
                }

                $requestedAmountKey = $targetKingdom->id . ':' . $gameUnit->id;
                $requestedAmounts[$requestedAmountKey] = ($requestedAmounts[$requestedAmountKey] ?? 0) + (int) ($unitRequest['unit_amount'] ?? 0);

                $activeManualQueueAmount = $manualQueueAmountMap[$targetKingdom->id][$gameUnit->id] ?? 0;
                $activeCapitalCityQueueAmount = $capitalCityQueueAmountMap[$targetKingdom->id][$gameUnit->id] ?? 0;

                if ($activeManualQueueAmount > 0 || $activeCapitalCityQueueAmount > 0) {
                    return 'One or more units are already queued for recruitment.';
                }

                $ownedAmount = $ownedUnitAmountMap[$targetKingdom->id][$gameUnit->id] ?? 0;

                if ($ownedAmount + $activeManualQueueAmount + $activeCapitalCityQueueAmount + $requestedAmounts[$requestedAmountKey] > KingdomMaxValue::MAX_UNIT) {
                    return 'One or more unit requests exceed the maximum allowed units.';
                }
            }
        }

        return null;
    }

    /**
     * Determine the time required for unit movement.
     *
     * @param Character $character
     * @param Kingdom|null $toKingdom
     * @param Kingdom $fromKingdom
     * @return int
     */
    private function determineTime(Character $character, Kingdom $toKingdom, Kingdom $fromKingdom): int
    {
        return $this->unitMovementService->getDistanceTime(
            $character,
            $toKingdom,
            $fromKingdom,
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
            'messages' => [],
            'started_at' => now(),
            'completed_at' => $minutes,
        ];
    }

    /**
     * Prepare the unit requests data.
     *
     * @param array $unitRequestsData
     * @param Kingdom|null $toKingdom
     * @param Collection $gameUnitsByName
     * @param array $activeManualUnitIdSet
     * @param array $activeCapitalUnitNameSet
     * @return array
     */
    private function prepareUnitRequests(array $unitRequestsData, Kingdom $toKingdom, Collection $gameUnitsByName): array
    {
        return collect($unitRequestsData)->filter(function ($unitRequest) use ($gameUnitsByName) {
            $unit = $gameUnitsByName->get($unitRequest['unit_name']);

            if (is_null($unit)) {
                return false;
            }

            return true;
        })->map(function ($unitRequest) use ($toKingdom, $gameUnitsByName) {
            $unit = $gameUnitsByName->get($unitRequest['unit_name']);

            return [
                'name' => $unitRequest['unit_name'],
                'amount' => $unitRequest['unit_amount'],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'costs' => $this->kingdomUnitResourceValidation->getCostsRequired($toKingdom, $unit, $unitRequest['unit_amount']),
            ];
        })->toArray();
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
