<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class BattleRewardQueueCharacterTransformer extends BaseTransformer
{
    public function transform(object $row): array
    {
        return [
            'character_id' => (int) $row->character_id,
            'character_name' => $row->character_name,
            'battle_requests' => (int) $row->battle_requests,
            'quest_requests' => (int) $row->quest_requests,
            'pending_count' => (int) $row->pending_count,
            'processing_count' => (int) $row->processing_count,
            'resumable_count' => (int) $row->resumable_count,
            'failed_count' => (int) $row->failed_count,
            'completed_count' => (int) $row->completed_count,
            'last_request_at' => $row->last_request_at,
        ];
    }
}
