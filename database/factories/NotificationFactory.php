<?php

namespace Database\Factories;

use App\Flare\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'title' => null,
            'message' => null,
            'status' => null,
            'type' => null,
            'read' => null,
            'url' => null,
            'adventure_id' => null,
        ];
    }
}
