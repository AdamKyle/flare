<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class ExplorationSummaryTransformer extends BaseTransformer
{
    public function transform(array $summary): array
    {
        return [
            'total_runs' => (int) $summary['total_runs'],
            'stopped_by_player' => (int) $summary['stopped_by_player'],
            'total_kills' => (int) $summary['total_kills'],
            'total_xp_gained' => (int) $summary['total_xp_gained'],
            'total_skill_xp_gained' => (int) $summary['total_skill_xp_gained'],
        ];
    }
}
