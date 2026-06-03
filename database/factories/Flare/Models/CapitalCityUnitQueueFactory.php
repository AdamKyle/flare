<?php

namespace Database\Factories\Flare\Models;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapitalCityUnitQueueFactory extends Factory
{
    protected $model = CapitalCityUnitQueue::class;

    public function definition(): array
    {
        return [
            'unit_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ];
    }
}
