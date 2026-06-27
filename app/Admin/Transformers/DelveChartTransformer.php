<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class DelveChartTransformer extends BaseTransformer
{
    public function transform(array $row): array
    {
        return [
            'period' => $row['period'],
            'runs' => (int) $row['runs'],
            'active' => (int) $row['active'],
            'completed' => (int) $row['completed'],
            'survived' => (int) $row['survived'],
            'died' => (int) $row['died'],
            'timeout' => (int) $row['timeout'],
        ];
    }
}
