<?php

namespace Database\Factories;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameLocationGemProgression;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameLocationGemProgressionFactory extends Factory
{
    protected $model = GameLocationGemProgression::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'game_location_gem_paramter_id' => GameLocationGemParamter::factory(),
            'level' => 1,
            'xp' => 0,
        ];
    }
}
