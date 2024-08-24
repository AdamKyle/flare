<?php

namespace Database\Factories;

use App\Flare\Models\Gem;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class GemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Sample',
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.20,
            'tertiary_atonement_amount' => 0.10,
        ];
    }
}
