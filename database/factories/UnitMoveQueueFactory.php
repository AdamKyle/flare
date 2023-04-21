<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\UnitMovementQueue;

class UnitMoveQueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UnitMovementQueue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $character = Character::first();

        return [
            'character_id'    => is_null($character) ? $character : null,
            'from_kingdom_id' => 1,
            'to_kingdom_id'   => 1,
            'units_moving'    => [],
            'completed_at'    => now()->addMinutes(45),
            'started_at'      => now(),
            'moving_to_x'     => 16,
            'moving_to_y'     => 16,
            'from_x'          => 0,
            'from_y'          => 0,
            'is_recalled'     => false,
            'is_returning'    => false,
        ];
    }
}
