<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CapitalCityKingdomLogHandler {

    public function __construct(private readonly UpdateKingdom $updateKingdom) {}

    /**
     * Potentially create a kingdom log.
     *
     * - If the buildings are: Rejected, Finished or Cancelled we create a kingdom log.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function possiblyCreateLogForQueue(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $buildingData = $this->createBuildingDataForLog($requestData);

        if (count($buildingData) === count($requestData)) {
            KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $capitalCityBuildingQueue->requested_kingdom,
                'to_kingdom_id' => $kingdom->id,
                'opened' => false,
                'additional_details' => [
                    'messages' => $capitalCityBuildingQueue->messages,
                    'building_data' => $buildingData,
                ],
                'status' => KingdomLogStatusValue::CAPITAL_CITY_BUILDING_REQUEST,
                'published' => true,
            ]);

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);

            $capitalCityBuildingQueue->delete();

            event(new UpdateCapitalCityBuildingQueueTable($character));
        }
    }

    /**
     * Create building data for the log.
     *
     * @param array $requestData
     * @return array
     */
    private function createBuildingDataForLog(array $requestData): array {
        $buildingData = [];

        foreach ($requestData as $data) {
            if ($data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                $data['secondary_status'] === CapitalCityQueueStatus::FINISHED ||
                $data['secondary_status'] === CapitalCityQueueStatus::CANCELLED
            ) {

                $building = KingdomBuilding::where('kingdom_id', $kingdom->id)->where('id', $data['building_id'])->first();

                $buildingData[] = [
                    'building_name' => $building->name,
                    'from_level' => $data['from_level'],
                    'to_level' => $data['to_level'],
                    'type' => $data['type'],
                    'status' => $data['secondary_status'],
                ];
            }
        }

        return $buildingData;
    }
}
