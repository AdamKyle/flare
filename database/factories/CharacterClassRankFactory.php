<?php

namespace Database\Factories;

use App\Flare\Models\CharacterClassRank;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterClassRankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterClassRank::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'game_class_id' => null,
            'current_xp' => 1,
            'required_xp' => 10,
            'level' => 1,
        ];
    }
}
