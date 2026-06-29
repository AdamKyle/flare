<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Character::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => 1,
            'name' => 'fake',
            'damage_stat' => 'dex',
            'game_race_id' => GameRace::factory(),
            'game_class_id' => GameClass::factory(),
            'xp' => 1,
            'xp_next' => 100,
            'str' => 1,
            'dur' => 1,
            'dex' => 1,
            'chr' => 1,
            'int' => 1,
            'agi' => 1,
            'focus' => 1,
            'ac' => 1,
            'gold' => 0,
            'can_attack' => true,
            'can_move' => true,
        ];
    }
}
