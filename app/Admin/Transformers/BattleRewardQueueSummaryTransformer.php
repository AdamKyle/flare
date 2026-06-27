<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class BattleRewardQueueSummaryTransformer extends BaseTransformer
{
    public function transform(array $summary): array
    {
        return [
            'queued' => (int) $summary['queued'],
            'pending' => (int) $summary['pending'],
            'processing' => (int) $summary['processing'],
            'resumable' => (int) $summary['resumable'],
            'completed' => (int) $summary['completed'],
            'failed' => (int) $summary['failed'],
        ];
    }
}
