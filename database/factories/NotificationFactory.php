<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Notification;

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
            'title'        => null,
            'message'      => null,
            'status'       => null,
            'type'         => null,
            'read'         => null,
            'url'          => null,
            'adventure_id' => null,
        ];
    }
}
