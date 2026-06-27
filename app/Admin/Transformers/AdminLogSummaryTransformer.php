<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminLogSummaryTransformer extends BaseTransformer
{
    public function transform(array $summary): array
    {
        return [
            'total' => (int) $summary['total'],
            'by_severity' => $summary['by_severity'],
            'chart' => $summary['chart'],
        ];
    }
}
