<?php

namespace Database\Factories;

use App\Flare\Models\GameMap;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameMap::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Surface',
            'path' => 'path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ];
    }
}
