<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;

trait CreateCharacterBattleRewardRequestStep
{
    /**
     * Create a CharacterBattleRewardRequestStep for tests, building a matching request when needed.
     */
    public function createCharacterBattleRewardRequestStep(array $options = []): CharacterBattleRewardRequestStep
    {
        if (! isset($options['character_battle_reward_request_id']) && isset($options['character_id'])) {
            $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $options['character_id']]);

            $options['character_battle_reward_request_id'] = $request->id;
        }

        return CharacterBattleRewardRequestStep::factory()->create($options);
    }
}
