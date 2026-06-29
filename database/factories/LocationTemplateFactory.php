<?php

namespace Database\Factories;

use App\Flare\Models\LocationTemplate;
use App\Flare\Values\LocationTemplateType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationTemplateFactory extends Factory
{
    protected $model = LocationTemplate::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->words(4, true),
            'description' => $this->faker->unique()->sentence(),
            'type' => LocationTemplateType::REGULAR->value,
            'is_port' => false,
            'can_players_enter' => true,
        ];
    }
}
