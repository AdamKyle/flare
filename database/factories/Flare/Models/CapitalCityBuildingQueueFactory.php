<?php

namespace Database\Factories\Flare\Models;

use App\Flare\Models\CapitalCityBuildingQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

class CapitalCityBuildingQueueFactory extends Factory
{
    protected $model = CapitalCityBuildingQueue::class;

    public function definition(): array
    {
        return [
            'building_request_data' => [],
            'messages' => [],
            'status' => 'travelling',
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ];
    }
}
