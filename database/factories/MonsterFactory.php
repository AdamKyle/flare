<?php

namespace Database\Factories;

use App\Flare\Models\Monster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MonsterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Monster::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(32),
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 1,
            'dur' => 1,
            'dex' => 1,
            'chr' => 1,
            'int' => 1,
            'agi' => 1,
            'focus' => 1,
            'ac' => 1,
            'health_range' => '1-8',
            'attack_range' => '1-6',
            'gold' => 25,
            'drop_check' => 6,
            'max_level' => 0,
            'game_map_id' => null,
            'criticality' => 0.03,
            'accuracy' => 0.01,
            'dodge' => 0.01,
            'casting_accuracy' => 0.01,
        ];
    }
}
