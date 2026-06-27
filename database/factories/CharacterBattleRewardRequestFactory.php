<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterBattleRewardRequestFactory extends Factory
{
    protected $model = CharacterBattleRewardRequest::class;

    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'source_id' => null,
            'handler_payload' => [],
            'status' => BattleRewardRequestStatus::PENDING,
            'failed_reason' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
