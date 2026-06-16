<?php

namespace Database\Factories;

use App\Flare\Models\ExplorationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExplorationLogFactory extends Factory
{
    protected $model = ExplorationLog::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
            'user_id' => 0,
            'character_automation_id' => 0,
            'monster_id' => 0,
            'attack_type' => 'attack',
            'starting_level' => null,
            'started_at' => now(),
            'ended_at' => null,
            'stopped_reason' => null,
            'stopped_by_player' => false,
            'fights' => 0,
            'kills' => 0,
            'weapon_damage' => 0,
            'spell_damage' => 0,
            'xp_gained' => 0,
            'skill_xp_gained' => 0,
            'faction_points_gained' => 0,
            'currencies_gained' => null,
            'summary' => null,
        ];
    }
}
