<?php

namespace App\Admin\Classes\Transformers;

use App\Flare\Models\GameClass;
use App\Game\Core\Values\CoreStatType;

class ClassFormOptionsTransformer
{
    /**
     * Transform the supplied internal Class form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'stats' => array_map(
                fn (CoreStatType $stat): string => $stat->value,
                CoreStatType::cases(),
            ),
            'classes' => $formOptions['classes']->map(fn (GameClass $gameClass): array => [
                'id' => $gameClass->id,
                'name' => $gameClass->name,
            ])->values()->all(),
        ];
    }
}
