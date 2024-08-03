<?php

namespace Database\Factories;

use App\Flare\Models\CharacterSnapShot;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterSnapShotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterSnapShot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => 1,
            'snap_shot' => [],
            'battle_simmulation_data' => [],
            'adventure_simmulation_data' => [],
        ];
    }
}
