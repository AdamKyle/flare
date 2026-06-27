<?php

namespace App\Admin\Transformers;

use App\Flare\Models\DelveExploration;
use App\Flare\Transformers\BaseTransformer;

class DelveRunTransformer extends BaseTransformer
{
    public function transform(DelveExploration $run): array
    {
        return [
            'id' => $run->id,
            'character_id' => $run->character_id,
            'character' => $run->relationLoaded('character') && ! is_null($run->character)
                ? ['name' => $run->character->name]
                : null,
            'started_at' => $run->started_at?->toDateTimeString(),
            'completed_at' => $run->completed_at?->toDateTimeString(),
            'increase_enemy_strength' => $run->increase_enemy_strength,
            'delve_logs' => $run->relationLoaded('delveLogs')
                ? $run->delveLogs->map(fn ($log): array => [
                    'id' => $log->id,
                    'outcome' => $log->outcome,
                    'pack_size' => $log->pack_size,
                    'increased_enemy_strength' => $log->increased_enemy_strength,
                ])->values()->all()
                : [],
        ];
    }
}
