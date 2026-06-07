<?php

namespace Database\Factories;

use App\Flare\Models\BuildingExpansionQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingExpansionQueueFactory extends Factory
{
    protected $model = BuildingExpansionQueue::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
            'kingdom_id' => 0,
            'building_id' => 0,
            'completed_at' => now()->addHours(1),
            'started_at' => now(),
        ];
    }
}
