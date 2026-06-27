<?php

namespace App\Admin\Transformers;

class FactionLoyaltyChartTransformer extends FactionLoyaltySummaryTransformer
{
    public function transform(array $row): array
    {
        return [
            'period' => $row['period'],
            'runs' => (int) $row['runs'],
            'active' => (int) $row['active'],
            'completed' => (int) $row['completed'],
        ];
    }
}
