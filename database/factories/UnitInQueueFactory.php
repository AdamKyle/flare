<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\UnitInQueue;

class UnitInQueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UnitInQueue::class;

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
            'amount'       => null,
            'completed_at' => now(),
            'started_at'   => now()->subMinutes(10),
        ];
    }
}
