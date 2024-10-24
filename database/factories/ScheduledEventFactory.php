<?php

namespace Database\Factories;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'raid_id' => null,
            'start_date' => now(),
            'end_date' => now(),
            'description' => 'Test',
            'currently_running' => false,
        ];
    }
}
