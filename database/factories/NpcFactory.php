<?php

namespace Database\Factories;

use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NpcFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Npc::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(10),
            'real_name' => Str::random(10),
            'type' => NpcTypes::KINGDOM_HOLDER,
            'game_map_id' => null,
            'x_position' => 32,
            'y_position' => 144,
        ];
    }
}
