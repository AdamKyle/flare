<?php

namespace App\Admin\Transformers;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Transformers\BaseTransformer;

class FactionLoyaltyRunTransformer extends BaseTransformer
{
    public function transform(FactionLoyaltyAutomation $run): array
    {
        return [
            'id' => $run->id,
            'character_id' => $run->character_id,
            'character' => $run->relationLoaded('character') && ! is_null($run->character)
                ? ['name' => $run->character->name]
                : null,
            'faction_loyalty_npc_id' => $run->faction_loyalty_npc_id,
            'npc_name' => $run->factionLoyaltyNpc?->npc?->name,
            'last_automation_action' => $run->last_automation_action,
            'last_automation_action_at' => $run->last_automation_action_at?->toDateTimeString(),
            'started_at' => $run->started_at?->toDateTimeString(),
            'completed_at' => $run->completed_at?->toDateTimeString(),
            'log' => $run->relationLoaded('log') && ! is_null($run->log)
                ? [
                    'id' => $run->log->id,
                    'fight_logs' => $run->log->fight_logs,
                    'crafting_logs' => $run->log->crafting_logs,
                ]
                : null,
        ];
    }
}
