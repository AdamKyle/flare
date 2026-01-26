<?php

namespace Database\Factories;

use App\Flare\Models\DelveExploration;
use Illuminate\Database\Eloquent\Factories\Factory;

class DelveExplorationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DelveExploration::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => 0,
            'monster_id' => 0,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => 'attack',
            'increase_enemy_strength' => 0,
            'battle_messages' => [['message_type'=> 'message']],
        ];
    }
}
