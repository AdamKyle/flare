<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\CharacterBattleRewardRequestStep;

trait CreateCharacterBattleReward
{
    public function createCharacterBattleRewardQueueState(array $options = []): CharacterBattleRewardQueueState
    {
        return CharacterBattleRewardQueueState::factory()->create($options);
    }

    public function createCharacterBattleRewardRequest(array $options = []): CharacterBattleRewardRequest
    {
        return CharacterBattleRewardRequest::factory()->create($options);
    }

    public function createCharacterBattleRewardRequestStep(array $options = []): CharacterBattleRewardRequestStep
    {
        return CharacterBattleRewardRequestStep::factory()->create($options);
    }

    public function createCharacterBattleRewardRequestMessage(array $options = []): CharacterBattleRewardRequestMessage
    {
        return CharacterBattleRewardRequestMessage::factory()->create($options);
    }
}
