<?php

namespace Database\Factories;

use App\Flare\Models\InventorySet;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventorySetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InventorySet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'is_equipped' => false,
            'can_be_equipped' => true,
        ];
    }
}
