<?php

namespace Database\Factories;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => 'test',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 100,
            'crafting_type' => 'weapon',
            'description' => 'sample',
            'can_resurrect' => false,
            'resurrection_chance' => 0.0,
            'can_use_on_other_items' => false,
        ];
    }
}
