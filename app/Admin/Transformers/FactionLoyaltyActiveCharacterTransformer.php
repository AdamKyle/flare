<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class FactionLoyaltyActiveCharacterTransformer extends BaseTransformer
{
    public function transform(array $character): array
    {
        return [
            'character_id' => (int) $character['character_id'],
            'character_name' => $character['character_name'],
            'npc_name' => $character['npc_name'],
            'last_action' => $character['last_action'],
            'last_action_at' => $character['last_action_at'],
            'started_at' => $character['started_at'],
            'last_fight_outcome' => $character['last_fight_outcome'],
            'last_fight_was_bounty_target' => $character['last_fight_was_bounty_target'],
            'failed_bounty_monster_name' => $character['failed_bounty_monster_name'],
            'failed_crafting_item_name' => $character['failed_crafting_item_name'],
        ];
    }
}
