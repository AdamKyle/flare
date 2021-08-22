<?php

namespace Database\Factories;

use Hash;
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
            'email'                   => $this->faker->unique()->safeEmail,
            'email_verified_at'       => now(),
            'password'                => Hash::make('ReallyLongPassword'),
            'remember_token'          => Str::random(10),
            'message_throttle_count'  => 0,
            'is_silenced'             => false,
            'can_speak_again_at'      => null,
            'is_banned'               => false,
            'unbanned_at'             => null,
            'ip_address'              => '127.0.0.1',
            'banned_reason'           => null,
            'un_ban_request'          => null,
            'adventure_email'         => true,
            'is_test'                 => true,
            'new_building_email'      => true,
            'upgraded_building_email' => true,
            'rebuilt_building_email'  => true,
            'kingdom_attack_email'    => true,
        ];
    }
}
