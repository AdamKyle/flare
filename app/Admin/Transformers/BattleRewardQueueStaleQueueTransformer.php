<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class BattleRewardQueueStaleQueueTransformer extends BaseTransformer
{
    public function __construct(private readonly BattleRewardQueueRequestTransformer $requestTransformer) {}

    public function transform(array $queue): array
    {
        return [
            'character_id' => (int) $queue['character_id'],
            'character_name' => $queue['character_name'],
            'queue_state_id' => (int) $queue['queue_state_id'],
            'started_at' => $queue['started_at'],
            'heartbeat_at' => $queue['heartbeat_at'],
            'stale_age_seconds' => $queue['stale_age_seconds'],
            'pending_request_count' => (int) $queue['pending_request_count'],
            'processing_request_count' => (int) $queue['processing_request_count'],
            'resumable_request_count' => (int) $queue['resumable_request_count'],
            'failed_request_count' => (int) $queue['failed_request_count'],
            'current_request_id' => $queue['current_request_id'],
            'current_request_source_type' => $queue['current_request_source_type'],
            'current_request_source_id' => $queue['current_request_source_id'],
            'current_ledger_step' => $queue['current_ledger_step'],
            'current_ledger_step_status' => $queue['current_ledger_step_status'],
            'current_ledger_step_heartbeat_at' => $queue['current_ledger_step_heartbeat_at'],
            'checkpoint_age_seconds' => $queue['checkpoint_age_seconds'],
            'un_emitted_message_count' => (int) $queue['un_emitted_message_count'],
            'oldest_pending_request_created_at' => $queue['oldest_pending_request_created_at'],
            'oldest_processing_request_created_at' => $queue['oldest_processing_request_created_at'],
            'requests' => array_map(
                fn (array $request): array => $this->requestTransformer->transformArray($request),
                $queue['requests'],
            ),
        ];
    }
}
