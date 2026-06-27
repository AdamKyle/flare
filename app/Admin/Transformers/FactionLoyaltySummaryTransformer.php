<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class FactionLoyaltySummaryTransformer extends BaseTransformer
{
    public function transform(array $summary): array
    {
        return [
            'total_runs' => (int) $summary['total_runs'],
            'active' => (int) $summary['active'],
            'completed' => (int) $summary['completed'],
        ];
    }
}
