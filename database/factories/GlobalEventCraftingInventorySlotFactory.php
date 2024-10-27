<?php

namespace Database\Factories;

use App\Flare\Models\GlobalEventCraftingInventorySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalEventCraftingInventorySlotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalEventCraftingInventorySlot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'global_event_crafting_inventory_id' => null,
            'item_id' => null,
        ];
    }
}
