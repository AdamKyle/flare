<?php

namespace Database\Factories;

use App\Flare\Models\SetSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SetSlotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SetSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'item_id' => null,
            'inventory_set_id' => null,
            'equipped' => false,
            'position' => null,
        ];
    }
}
