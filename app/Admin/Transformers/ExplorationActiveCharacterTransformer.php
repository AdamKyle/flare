<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class ExplorationActiveCharacterTransformer extends BaseTransformer
{
    public function transform(array $character): array
    {
        return [
            'character_id' => (int) $character['character_id'],
            'character_name' => $character['character_name'],
            'monster_name' => $character['monster_name'],
            'attack_type' => $character['attack_type'],
            'started_at' => $character['started_at'],
            'completed_at' => $character['completed_at'],
        ];
    }
}
