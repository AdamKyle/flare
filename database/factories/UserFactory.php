<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Flare\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email'                  => $this->faker->unique()->safeEmail,
            'email_verified_at'      => now(),
            'password'               => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // => password
            'remember_token'         => Str::random(10),
            'message_throttle_count' => 0,
            'is_silenced'            => false,
            'can_speak_again_at'     => null,
        ];
    }
}
