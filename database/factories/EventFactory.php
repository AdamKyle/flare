<?php

namespace Database\Factories;

use App\Flare\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => null,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(5),
            'raid_id' => null,
        ];
    }
}
