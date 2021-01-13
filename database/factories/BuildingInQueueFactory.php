<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Building;

class BuildingInQueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'kingdom_id'   => null,
            'building_id'  => null,
            'to_level'     => null,
            'completed_at' => now(),
            'started_at'   => now()->subMinutes(10),
        ];
    }
}
