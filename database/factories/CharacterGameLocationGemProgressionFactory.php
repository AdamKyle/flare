<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\GameLocationGemParamter;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGameLocationGemProgressionFactory extends Factory
{
    protected $model = CharacterGameLocationGemProgression::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'game_location_gem_paramter_id' => GameLocationGemParamter::factory(),
            'level' => 1,
            'xp' => 0,
        ];
    }
}
