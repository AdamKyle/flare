<?php

namespace Database\Factories;

use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterClassSpecialtiesEquippedFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterClassSpecialtiesEquipped::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'game_class_special_id' => null,
            'level' => 0,
            'current_xp' => 1,
            'required_xp' => 10,
            'equipped' => false,
        ];
    }
}
