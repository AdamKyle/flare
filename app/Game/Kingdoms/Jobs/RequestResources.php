<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Messages\Events\ServerMessageEvent;

class RequestResources implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $characterId
     * @param int $requestingKingdomId
     * @param int $requestingFromKingdomId
     * @param array $resourcesToTransfer
     * @param array $unitsInMovement
     * @param array $additionalMessagesForLog
     * @param int|null $capitalCityQueueId
     * @param int|null $buildingId
     * @param int|null $unitId
     */
    public function __construct(private readonly int $characterId,
                                private readonly int $requestingKingdomId,
                                private readonly int $requestingFromKingdomId,
                                private readonly array $resourcesToTransfer,
                                private readonly array $unitsInMovement,
                                private readonly array $additionalMessagesForLog,
                                private readonly int|null $capitalCityQueueId = null,
                                private readonly int|null $buildingId = null,
                                private readonly int|null $unitId = null) {}

    /**
     * Execute the job.
     *
     * @param UpdateKingdom $updateKingdom
     * @param DistanceCalculation $distanceCalculation
     * @param CapitalCityBuildingManagement $capitalCityBuildingManagement
     * @param CapitalCityUnitManagement $capitalCityUnitManagement
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom, DistanceCalculation $distanceCalculation, CapitalCityBuildingManagement $capitalCityBuildingManagement, CapitalCityUnitManagement $capitalCityUnitManagement): void {

        $requestedKingdom = Kingdom::find($this->requestingKingdomId);
        $requestingFromKingdom = Kingdom::find($this->requestingFromKingdomId);

        if ($requestedKingdom->character_id !== $this->characterId) {
            KingdomLog::create([
                'character_id' => $requestedKingdom->character_id,
                'from_kingdom_id' => $requestingFromKingdom->id,
                'to_kingdom_id' => $requestedKingdom->id,
                'opened' => false,
                'additional_details' => [
                    'kingdom_data' => [
                        'reason' => 'You lost the resources because the requesting kingdom is no longer yours.
                    Your spearmen tried to save the people, but they were cut down.'
                    ]
                ],
                'status' => KingdomLogStatusValue::RESOURCES_LOST,
            ]);
        }

        foreach ($this->resourcesToTransfer as $resource => $amount) {
            $newAmount = $requestedKingdom->{'current_' . $resource} + $amount;

            if ($newAmount > $requestedKingdom->{'max_' . $resource}) {
                $newAmount = $requestedKingdom->{'max_' . $resource};
            }

            $requestedKingdom->{'current_' . $resource} = $newAmount;

            $requestedKingdom->save();

            $requestedKingdom = $requestedKingdom->refresh();
        }

        $logDetails = [
            'resource_request_log' => $this->buildRequestLog($requestedKingdom, $requestingFromKingdom)
        ];

        KingdomLog::create([
            'character_id' => $requestedKingdom->character_id,
            'from_kingdom_id' => $requestingFromKingdom->id,
            'to_kingdom_id' => $requestedKingdom->id,
            'opened' => false,
            'additional_details' => $logDetails,
            'status' => KingdomLogStatusValue::RESOURCES_REQUESTED,
            'published' => true,
        ]);

        $updateKingdom->updateKingdom($requestedKingdom);

        $updateKingdom->updateKingdomLogs($requestedKingdom->character, true);

        $timeToKingdom = $this->getMinutesForTravel($requestedKingdom, $requestingFromKingdom, $distanceCalculation);

        $unitMovementQueue =  UnitMovementQueue::create(
            $this->buildUnitMovementQueue($requestedKingdom, $requestingFromKingdom, $timeToKingdom)
        );

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::where('id', $this->capitalCityQueueId)->where('kingdom_id', $requestedKingdom->kingdom_id)->first();
        $capitalCityUnitQueue = CapitalCityUnitQueue::where('id', $this->capitalCityQueueId)->where('kingdom_id', $requestingFromKingdom->id)->first();

        if (!is_null($capitalCityBuildingQueue) && !is_null($this->buildingId)) {

            $buildingRequestQueue = $capitalCityBuildingQueue->building_request_data;

            $building = $requestingFromKingdom->buildings()->find($this->buildingId);

            foreach ($buildingRequestQueue as $index => $requestData) {
                if ($requestData['building_id'] === $this->buildingId) {
                    $buildingRequestQueue[$index]['secondary_status'] = CapitalCityQueueStatus::BUILDING;
                }
            }

            $capitalCityBuildingQueue->update([
                'building_request_data' => $buildingRequestQueue,
            ]);

            $capitalCityBuildingQueue = $capitalCityBuildingManagement->refresh();

            event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character, $capitalCityBuildingQueue->requestingKingdom));

            $capitalCityBuildingManagement->handleBuildingRequest($capitalCityBuildingQueue->refresh(), $building, $requestingFromKingdom->character);
        }

        if (!is_null($capitalCityUnitQueue) && !is_null($this->unitId)) {
            $buildingRequestQueue = $capitalCityBuildingQueue->building_request_data;

            foreach ($buildingRequestQueue as $index => $requestData) {
                if ($requestData['unit_id'] === $this->unitId) {
                    $buildingRequestQueue[$index]['secondary_status'] = CapitalCityQueueStatus::RECRUITING;
                }
            }

            $capitalCityUnitQueue->update([
                'unit_request_data' => $buildingRequestQueue,
            ]);

            $capitalCityUnitManagement->recruitUnits($capitalCityUnitQueue->refresh());
        }

        $this->sendOffEvents($requestedKingdom, $requestingFromKingdom, $unitMovementQueue);
    }

    private function buildRequestLog(Kingdom $requestedKingdom, Kingdom $requestingFromKingdom): array {
        return [
            'kingdom_who_requested' => $requestedKingdom->name . ' At (X/Y): ' . $requestedKingdom->x_position . '/' . $requestedKingdom->y_position . ' on plane: ' . $requestedKingdom->gameMap->name,
            'kingdom_requested_from' => $requestingFromKingdom->name . ' At (X/Y): ' . $requestingFromKingdom->x_position . '/' . $requestingFromKingdom->y_position . ' on plane: ' . $requestingFromKingdom->gameMap->name,
            'resource_details' => $this->resourcesToTransfer,
            'message' => 'Resources have ben delivered.',
            'additional_messages' => $this->additionalMessagesForLog,
        ];

    }

    private function buildUnitMovementQueue(Kingdom $requestedKingdom, Kingdom $requestFromKingdom, int $completedAtMinutes): array {
        return [
            'character_id' => $requestedKingdom->character->id,
            'from_kingdom_id' => $requestedKingdom->id,
            'to_kingdom_id' => $requestFromKingdom->id,
            'units_moving' => $this->unitsInMovement,
            'completed_at' => now()->addMinutes($completedAtMinutes),
            'started_at' => now(),
            'moving_to_x' => $requestFromKingdom->x_position,
            'moving_to_y' => $requestFromKingdom->y_position,
            'from_x' => $requestedKingdom->x_position,
            'from_y' => $requestedKingdom->y_position,
            'is_attacking' => false,
            'is_recalled' => false,
            'is_returning' => true,
            'is_moving' => false,
            'resources_requested' => false,
        ];
    }

    private function getMinutesForTravel(Kingdom $requestedKingdom, Kingdom $requestFromKingdom, DistanceCalculation $distanceCalculation): int {
        $pixelDistance = $distanceCalculation->calculatePixel($requestFromKingdom->x_position, $requestFromKingdom->y_position,
            $requestedKingdom->x_position, $requestedKingdom->y_position);

        return $distanceCalculation->calculateMinutes($pixelDistance);
    }

    private function sendOffEvents(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom, UnitMovementQueue $unitMovementQueue): void {

        $user = $requestingFromKingdom->character->user;

        event(new UpdateKingdomQueues($requestingKingdom));
        event(new UpdateKingdomQueues($requestingFromKingdom));

        $minutes = (new Carbon($unitMovementQueue->completed_at))->diffInMinutes($unitMovementQueue->started_at);

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);

        event(new ServerMessageEvent($user, 'Your resources were dropped off and now the spearmen and (possibly - if sent along) Airship are headed home again.'));
    }
}
