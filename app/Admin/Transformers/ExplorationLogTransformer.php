<?php

namespace App\Admin\Transformers;

use App\Flare\Models\ExplorationLog;
use App\Flare\Transformers\BaseTransformer;

class ExplorationLogTransformer extends BaseTransformer
{
    public function transform(ExplorationLog $log): array
    {
        return [
            'id' => $log->id,
            'character_id' => $log->character_id,
            'character' => $log->relationLoaded('character') && ! is_null($log->character)
                ? ['name' => $log->character->name]
                : null,
            'monster_id' => $log->monster_id,
            'attack_type' => $log->attack_type,
            'starting_level' => $log->starting_level,
            'started_at' => $log->started_at?->toDateTimeString(),
            'ended_at' => $log->ended_at?->toDateTimeString(),
            'stopped_reason' => $log->stopped_reason,
            'stopped_by_player' => (bool) $log->stopped_by_player,
            'fights' => (int) $log->fights,
            'kills' => (int) $log->kills,
            'xp_gained' => (int) $log->xp_gained,
            'skill_xp_gained' => (int) $log->skill_xp_gained,
        ];
    }
}
