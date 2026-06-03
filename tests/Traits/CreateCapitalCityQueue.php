<?php

namespace Tests\Traits;

use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

trait CreateCapitalCityQueue
{
    public function createCapitalCityBuildingQueue(array $options = []): CapitalCityBuildingQueue
    {
        return CapitalCityBuildingQueue::factory()->create(array_merge([
            'character_id' => 1,
            'kingdom_id' => 1,
            'requested_kingdom' => 1,
            'building_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ], $options));
    }

    public function createCapitalCityBuildingCancellation(array $options = []): CapitalCityBuildingCancellation
    {
        return CapitalCityBuildingCancellation::factory()->create(array_merge([
            'building_id' => 1,
            'kingdom_id' => 1,
            'request_kingdom_id' => 1,
            'character_id' => 1,
            'capital_city_building_queue_id' => 1,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'travel_time_completed_at' => now()->addHour(),
        ], $options));
    }

    public function createCapitalCityUnitQueue(array $options = []): CapitalCityUnitQueue
    {
        return CapitalCityUnitQueue::factory()->create(array_merge([
            'character_id' => 1,
            'kingdom_id' => 1,
            'requested_kingdom' => 1,
            'unit_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ], $options));
    }

    public function createCapitalCityUnitCancellation(array $options = []): CapitalCityUnitCancellation
    {
        return CapitalCityUnitCancellation::factory()->create(array_merge([
            'unit_id' => 1,
            'kingdom_id' => 1,
            'request_kingdom_id' => 1,
            'character_id' => 1,
            'capital_city_unit_queue_id' => 1,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'travel_time_completed_at' => now()->addHour(),
        ], $options));
    }

    public function createCapitalCityResourceRequest(array $options = []): CapitalCityResourceRequest
    {
        return CapitalCityResourceRequest::factory()->create(array_merge([
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ], $options));
    }
}