<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\CharacterBoon;

class CharacterBoonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterBoon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'         => null,
            'type'                 => null,
            'stat_bonus'           => null,
            'affect_skill_type'    => null,
            'skill_bonus'          => null,
            'skill_training_bonus' => null,
            'started'              => null,
            'complete'             => null,
        ];
    }
}
