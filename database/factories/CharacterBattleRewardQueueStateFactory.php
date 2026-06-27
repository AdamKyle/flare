<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardQueueState;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterBattleRewardQueueStateFactory extends Factory
{
    protected $model = CharacterBattleRewardQueueState::class;

    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'is_processing' => false,
            'started_at' => null,
            'heartbeat_at' => null,
        ];
    }
}
