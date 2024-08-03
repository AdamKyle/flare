<?php

namespace Database\Factories;

use App\Flare\Models\GameRace;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameRaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameRace::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Sample Race',
        ];
    }
}
