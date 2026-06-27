<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class BattleRewardQueueRepairResultTransformer extends BaseTransformer
{
    public function transform(array $result): array
    {
        return [
            'repaired_queue_state_count' => (int) $result['repaired_queue_state_count'],
            'resumed_processing_request_count' => (int) $result['resumed_processing_request_count'],
            'legacy_failed_processing_request_count' => (int) $result['legacy_failed_processing_request_count'],
            'restarted_processor_count' => (int) $result['restarted_processor_count'],
            'cleared_inactive_queue_state_count' => (int) $result['cleared_inactive_queue_state_count'],
            'resumable_step_count' => (int) $result['resumable_step_count'],
            'un_emitted_message_count' => (int) $result['un_emitted_message_count'],
        ];
    }
}
