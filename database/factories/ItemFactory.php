<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\Item;

class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'                => 'test',
            'type'                => 'weapon',
            'base_damage'         => 10,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ];
    }
}
