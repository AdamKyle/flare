<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\CharacterBoon;

class CharacterBoonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterBoon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => null,
            'item_id' => null,
            'last_for_minutes' => null,
            'amount_used' => null,
            'started' => null,
            'complete' => null,
        ];
    }
}
