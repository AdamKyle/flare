<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class BattleRewardQueueChartTransformer extends BaseTransformer
{
    public function transform(array $row): array
    {
        return [
            'period' => $row['period'],
            'pending' => (int) $row['pending'],
            'processing' => (int) $row['processing'],
            'resumable' => (int) $row['resumable'],
            'completed' => (int) $row['completed'],
            'failed' => (int) $row['failed'],
        ];
    }
}
