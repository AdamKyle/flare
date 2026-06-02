<?php

namespace Database\Factories\Flare\Models;

use App\Flare\Models\CapitalCityUnitCancellation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapitalCityUnitCancellationFactory extends Factory
{
    protected $model = CapitalCityUnitCancellation::class;

    public function definition(): array
    {
        return [
            'unit_id' => 1,
            'kingdom_id' => 1,
            'request_kingdom_id' => 1,
            'character_id' => 1,
            'capital_city_unit_queue_id' => 1,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'travel_time_completed_at' => now()->addHour(),
        ];
    }
}
