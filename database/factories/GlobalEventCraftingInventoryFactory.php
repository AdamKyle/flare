<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventCraftingInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventCraftingInventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventCraftingInventory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'global_event_id' => null,
            'character_id' => null,
        ];
    }
}
