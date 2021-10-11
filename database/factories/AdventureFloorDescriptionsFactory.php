<?php

namespace Database\Factories;

use App\Flare\Models\AdventureFloorDescriptions;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdventureFloorDescriptionsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdventureFloorDescriptions::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'adventure_id' => null,
            'description'  => null,
        ];
    }
}
