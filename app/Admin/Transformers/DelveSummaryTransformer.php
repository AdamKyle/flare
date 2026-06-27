<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class DelveSummaryTransformer extends BaseTransformer
{
    public function transform(array $summary): array
    {
        return [
            'total_runs' => (int) $summary['total_runs'],
            'active' => (int) $summary['active'],
            'completed' => (int) $summary['completed'],
            'total_survived' => (int) $summary['total_survived'],
            'total_died' => (int) $summary['total_died'],
            'total_timeout' => (int) $summary['total_timeout'],
        ];
    }
}
