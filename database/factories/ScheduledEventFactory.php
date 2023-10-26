<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\EventType;

class ScheduledEventFactory extends Factory {
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
    public function definition() {
        return [
            'event_type'         => EventType::MONTHLY_PVP,
            'raid_id'            => null,
            'start_date'         => now(),
            'end_date'           => now(),
            'description'        => 'Test',
            'currently_running'  => false,
        ];
    }
}
