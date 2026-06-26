<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterBattleRewardRequestStepFactory extends Factory
{
    protected $model = CharacterBattleRewardRequestStep::class;

    public function definition(): array
    {
        return [
            'character_battle_reward_request_id' => CharacterBattleRewardRequest::factory(),
            'character_id' => Character::factory(),
            'step_name' => BattleRewardStepName::BUILD_REWARD_PLAN,
            'status' => BattleRewardStepStatus::PENDING,
            'payload_json' => null,
            'result_json' => null,
            'checkpoint_json' => null,
            'started_at' => null,
            'heartbeat_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'failed_reason' => null,
            'attempts' => 0,
        ];
    }
}
