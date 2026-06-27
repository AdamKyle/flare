<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class ExplorationChartTransformer extends BaseTransformer
{
    public function transform(array $row): array
    {
        return [
            'period' => $row['period'],
            'runs' => (int) $row['runs'],
            'kills' => (int) $row['kills'],
            'xp' => (int) $row['xp'],
            'skill_xp' => (int) $row['skill_xp'],
            'active' => (int) $row['active'],
            'completed' => (int) $row['completed'],
        ];
    }
}
