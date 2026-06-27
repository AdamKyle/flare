<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminBugChartTransformer extends BaseTransformer
{
    public function transform(array $row): array
    {
        return [
            'period' => $row['period'],
            'occurrences' => (int) $row['occurrences'],
        ];
    }
}
