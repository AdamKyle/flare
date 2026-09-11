<?php

namespace Database\Factories;

use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameMapGemProgression;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapGemProgressionFactory extends Factory
{
    protected $model = GameMapGemProgression::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'game_map_gem_paramter_id' => GameMapGemParamter::factory(),
            'level' => 1,
            'xp' => 0,
        ];
    }
}
