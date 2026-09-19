<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Traits\SafelyBroadcastsEvents;

class BattleRewardLiveUpdateService
{
    use SafelyBroadcastsEvents;

    /**
     * Publish the minimal current Character reward state through the
     * existing partial Character websocket contract, immediately after the
     * request's authoritative reward mutations are safely complete.
     *
     * @param int $characterId
     * @return void
     */
    public function broadcast(int $characterId): void
    {
        $character = Character::find($characterId);

        if (is_null($character)) {
            return;
        }

        $partial = [
            'level' => $character->level,
            'xp' => $character->xp,
            'xp_next' => $character->xp_next,
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
        ];

        $this->safelyDispatchBroadcastEvent(
            new UpdateBaseCharacterInformation($character->user, ['data' => $partial]),
            ['character_id' => $characterId],
        );
    }
}
