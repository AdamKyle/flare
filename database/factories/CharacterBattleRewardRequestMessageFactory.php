<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterBattleRewardRequestMessageFactory extends Factory
{
    protected $model = CharacterBattleRewardRequestMessage::class;

    public function definition(): array
    {
        return [
            'character_battle_reward_request_id' => CharacterBattleRewardRequest::factory(),
            'character_id' => Character::factory(),
            'user_id' => User::factory(),
            'step_name' => null,
            'message' => 'Reward message',
            'message_id' => null,
            'source' => null,
            'item_id' => null,
            'link_text' => null,
            'emitted_at' => null,
        ];
    }
}
