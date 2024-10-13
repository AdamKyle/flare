<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CapitalCityKingdomLogHandler
{

    public function __construct(private readonly UpdateKingdom $updateKingdom) {}

    /**
     * Potentially create a kingdom log.
     *
     * - If the buildings are: Rejected, Finished or Cancelled we create a kingdom log.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function possiblyCreateLogForBuildingQueue(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $buildingData = $this->createBuildingDataForLog($kingdom, $requestData);

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

    public function possiblyCreateLogForUnitQueue(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        $requestData = $capitalCityUnitQueue->unit_request_data;
        $kingdom = $capitalCityUnitQueue->kingdom;
        $character = $capitalCityUnitQueue->character;

        $unitData = $this->createUnitDataForLog($requestData);

        if (count($unitData) === 0 && count($requestData) === 0) {

            $capitalCityUnitQueue->delete();

            event(new UpdateCapitalCityUnitQueueTable($character));

            return;
        }

        if (count($unitData) === count($requestData)) {

            KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $capitalCityUnitQueue->requested_kingdom,
                'to_kingdom_id' => $kingdom->id,
                'opened' => false,
                'additional_details' => [
                    'messages' => $capitalCityUnitQueue->messages,
                    'unit_data' => $unitData,
                ],
                'status' => KingdomLogStatusValue::CAPITAL_CITY_UNIT_REQUEST,
                'published' => true,
            ]);

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);

            $capitalCityUnitQueue->delete();

            event(new UpdateCapitalCityUnitQueueTable($character));
        }
    }

    /**
     * Create building data for the log.
     *
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    private function createBuildingDataForLog(Kingdom $kingdom, array $requestData): array
    {
        $buildingData = [];

        foreach ($requestData as $data) {
            if (
                $data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
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

    private function createUnitDataForLog(array $requestData): array
    {
        $unitData = [];

        foreach ($requestData as $data) {
            if (
                $data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                $data['secondary_status'] === CapitalCityQueueStatus::FINISHED ||
                $data['secondary_status'] === CapitalCityQueueStatus::CANCELLED
            ) {

                $unitData[] = [
                    'unit_name' => $data['name'],
                    'amount_requested' => $data['amount'],
                    'status' => $data['secondary_status'],
                ];
            }
        }

        return $unitData;
    }
}
