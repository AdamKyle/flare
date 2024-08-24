<?php

namespace Database\Factories;

use App\Flare\Models\GameClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameClassFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameClass::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Fighter',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'str_mod' => 0,
        ];
    }
}
