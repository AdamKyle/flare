<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\GameMapGemParamter;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGameMapGemProgressionFactory extends Factory
{
    protected $model = CharacterGameMapGemProgression::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'game_map_gem_paramter_id' => GameMapGemParamter::factory(),
            'level' => 1,
            'xp' => 0,
        ];
    }
}
