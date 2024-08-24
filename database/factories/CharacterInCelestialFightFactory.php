<?php

namespace Database\Factories;

use App\Flare\Models\CharacterInCelestialFight;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterInCelestialFightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterInCelestialFight::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'celestial_fight_id' => null,
            'character_max_health' => null,
            'character_current_health' => null,
        ];
    }
}
