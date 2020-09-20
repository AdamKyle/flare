<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Monster;

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
            'name'         => 'Goblin',
            'damage_stat'  => 'str',
            'xp'           => 10,
            'str'          => 1,
            'dur'          => 6,
            'dex'          => 7,
            'chr'          => 8,
            'int'          => 8,
            'ac'           => 6,
            'health_range' => '1-8',
            'attack_range' => '1-6',
            'gold'         => 25,
            'drop_check'   => 6,
            'max_level'    => 0,
        ];
    }
}
