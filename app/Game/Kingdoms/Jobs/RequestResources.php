<?php

namespace App\Game\Kingdoms\Jobs;

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
     */
    public function __construct(private readonly int $characterId,
                                private readonly int $requestingKingdomId,
                                private readonly int $requestingFromKingdomId,
                                private readonly array $resourcesToTransfer,
                                private readonly array $unitsInMovement,
                                private readonly array $additionalMessagesForLog) {}

    /**
     * Execute the job.
     *
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom, DistanceCalculation $distanceCalculation): void {

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
        ]);

        $updateKingdom->updateKingdom($requestedKingdom);

        $updateKingdom->updateKingdomLogs($requestedKingdom->character);

        $timeToKingdom = $this->getMinutesForTravel($requestedKingdom, $requestingFromKingdom, $distanceCalculation);

        $unitMovementQueue =  UnitMovementQueue::create(
            $this->buildUnitMovementQueue($requestedKingdom, $requestingFromKingdom, $timeToKingdom)
        );

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
            'moving_to_x' => $requestedKingdom->x_position,
            'moving_to_y' => $requestedKingdom->y_position,
            'from_x' => $requestFromKingdom->x_position,
            'from_y' => $requestFromKingdom->y_position,
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

        event(new ServerMessageEvent($user, 'Your resources were dropped of and now the spearmen are headed home again.'));
    }
}
