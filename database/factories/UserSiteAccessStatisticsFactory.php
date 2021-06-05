<?php

namespace Database\Factories;

use App\Flare\Models\UserSiteAccessStatistics;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSiteAccessStatisticsFactory extends Factory
{


    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserSiteAccessStatistics::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'amount_signed_in'  => 0,
            'amount_registered' => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }
}
