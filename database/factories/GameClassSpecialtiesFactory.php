<?php

namespace Database\Factories;

use App\Flare\Models\GameClassSpecial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GameClassSpecialtiesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameClassSpecial::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'game_class_id' => null,
            'name' => Str::random(12),
            'description' => Str::random(10),
            'requires_class_rank_level' => 0,
            'specialty_damage' => null,
            'increase_specialty_damage_per_level' => null,
            'specialty_damage_uses_damage_stat_amount' => null,
            'base_damage_mod' => 0.10,
            'base_ac_mod' => null,
            'base_healing_mod' => null,
            'base_spell_damage_mod' => null,
            'health_mod' => null,
            'base_damage_stat_increase' => null,
        ];
    }
}
