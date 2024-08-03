<?php

namespace Database\Factories;

use App\Game\Messages\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'message' => 'Test message',
            'from_user' => null,
            'to_user' => null,
            'x_position' => 16,
            'y_position' => 16,
        ];
    }
}
