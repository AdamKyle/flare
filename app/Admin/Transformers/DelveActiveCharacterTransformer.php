<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class DelveActiveCharacterTransformer extends BaseTransformer
{
    public function transform(array $character): array
    {
        return [
            'character_id' => (int) $character['character_id'],
            'character_name' => $character['character_name'],
            'started_at' => $character['started_at'],
            'increase_enemy_strength' => $character['increase_enemy_strength'],
            'increase_percentage' => $character['increase_percentage'],
            'outcome_counts' => [
                'survived' => (int) $character['outcome_counts']['survived'],
                'died' => (int) $character['outcome_counts']['died'],
                'timeout' => (int) $character['outcome_counts']['timeout'],
                'error' => (int) $character['outcome_counts']['error'],
            ],
            'total_encounters' => (int) $character['total_encounters'],
            'avg_pack_size' => $character['avg_pack_size'],
        ];
    }
}
