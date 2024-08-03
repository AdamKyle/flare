<?php

namespace Database\Factories;

use App\Flare\Models\QuestItemSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestItemSlotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuestItemSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'inventory_id' => null,
            'item_id' => null,
        ];
    }
}
