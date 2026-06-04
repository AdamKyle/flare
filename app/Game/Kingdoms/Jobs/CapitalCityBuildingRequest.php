<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CapitalCityBuildingRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $resourceTypes = [
        'wood',
        'clay',
        'stone',
        'iron',
    ];

    public function __construct(protected readonly int $capitalCityQueueId) {}

    public function handle(
        CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService
    ): void {

        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            return;
        }

        if (! $queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = now()->diffInMinutes($queueData->completed_at);

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityBuildingRequest::dispatch(
                    $this->capitalCityQueueId,
                )->onConnection('long_running')->onQueue('default_long')->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->handleBuilding($queueData, $capitalCityKingdomLogHandler, $kingdomMaxResourceRecalculationService);
    }

    private function handleBuilding(
        CapitalCityBuildingQueue $queueData,
        CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService
    ): void {
        $buildingRequestData = $queueData->building_request_data;
        $messages = $queueData->messages ?? [];
        $kingdom = $queueData->kingdom;

        $invalidSecondaryTypes = [
            CapitalCityQueueStatus::REJECTED,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::CANCELLED,
        ];

        foreach ($buildingRequestData as $index => $requestData) {

            if (in_array($requestData['secondary_status'], $invalidSecondaryTypes)) {
                continue;
            }

            $building = $kingdom->buildings()->find($requestData['building_id']);

            if (is_null($building)) {
                Log::error('Capital city building request rejected because the queued building is missing.', [
                    'queue_id' => $queueData->id,
                    'kingdom_id' => $kingdom->id,
                    'kingdom_name' => $kingdom->name,
                    'building_id' => $requestData['building_id'],
                    'building_name' => $requestData['building_name'],
                    'request_type' => $requestData['type'],
                ]);

                $messages[] = $requestData['building_name'].' does not seem to exist in this kingdom. If this is a bug screenshot it and submit a bug report with the name of your kingdom.';
                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            if ($requestData['type'] === 'upgrade') {
                $invalidUpgradeMessage = $this->getInvalidUpgradeMessage($queueData, $building, $requestData);

                if (! is_null($invalidUpgradeMessage)) {
                    $messages[] = $invalidUpgradeMessage;
                    $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                    continue;
                }

                $this->handleUpgradingBuilding($building, $requestData['to_level']);
                $kingdomMaxResourceRecalculationService->recalculate($kingdom);

                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;

                continue;
            }

            if ($requestData['type'] === 'repair') {
                $this->handleRebuildingBuilding($building);

                $messages[] = $building->name.' has been restored to its former glory!';
                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
            }
        }

        $queueData->update([
            'building_request_data' => $buildingRequestData,
            'messages' => $messages,
            'status' => CapitalCityQueueStatus::FINISHED,
        ]);

        $queueData = $queueData->refresh();

        $capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($queueData);
    }

    private function handleUpgradingBuilding(KingdomBuilding $building, int $toLevel): void
    {

        $building->update(['level' => $toLevel]);

        $building = $building->refresh();

        $building->update([
            'current_defence' => $building->defence,
            'current_durability' => $building->durability,
            'max_defence' => $building->defence,
            'max_durability' => $building->durability,
        ]);

    }

    private function getInvalidUpgradeMessage(
        CapitalCityBuildingQueue $queueData,
        KingdomBuilding $building,
        array $requestData
    ): ?string {
        if ($building->level >= $building->gameBuilding->max_level) {
            return $building->name.' has been rejected: Building is already max level.';
        }

        if ((int) $requestData['to_level'] > $building->gameBuilding->max_level) {
            return $building->name.' has been rejected: Requested level is over max level.';
        }

        if ((int) $requestData['from_level'] !== $building->level) {
            Log::error('Capital city building request rejected because the queued building level no longer matches the current building level.', [
                'queue_id' => $queueData->id,
                'kingdom_id' => $queueData->kingdom_id,
                'kingdom_name' => $queueData->kingdom->name,
                'building_id' => $building->id,
                'building_name' => $building->name,
                'current_level' => $building->level,
                'from_level' => $requestData['from_level'],
                'to_level' => $requestData['to_level'],
            ]);

            return 'Something is wrong for '.$building->name.', the level to advance from no longer matches the current building level. Please screen shot this and report a bug and include your kingdom name.';
        }

        return null;
    }

    private function handleRebuildingBuilding(KingdomBuilding $building): void
    {
        $building->update([
            'current_durability' => $building->max_durability,
        ]);

        $building = $building->refresh();
        $kingdom = $building->kingdom;

        if ($building->morale_increase > 0) {

            $newMorale = $kingdom->current_morale + $building->morale_increase;

            if ($newMorale > 1) {
                $newMorale = 1;
            }

            $kingdom->update([
                'current_morale' => $newMorale,
            ]);
        }
    }
}
