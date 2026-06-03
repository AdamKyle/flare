<?php

namespace Database\Factories\Flare\Models;

use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapitalCityBuildingCancellationFactory extends Factory
{
    protected $model = CapitalCityBuildingCancellation::class;

    public function definition(): array
    {
        return [
            'building_id' => 1,
            'kingdom_id' => 1,
            'request_kingdom_id' => 1,
            'character_id' => 1,
            'capital_city_building_queue_id' => 1,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'travel_time_completed_at' => now()->addHour(),
        ];
    }
}
