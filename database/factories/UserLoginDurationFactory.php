<?php

namespace Database\Factories;

use App\Flare\Models\UserLoginDuration;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserLoginDurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserLoginDuration::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'logged_in_at' => null,
            'logged_out_at' => null,
            'last_activity' => null,
            'duration_in_seconds' => null,
            'last_heart_beat' => null,
        ];
    }
}
