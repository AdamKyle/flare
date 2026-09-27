<?php

namespace App\Admin\Skills\Transformers;

use App\Flare\Models\GameClass;
use App\Game\Skills\Values\SkillTypeValue;

class SkillFormOptionsTransformer
{
    /**
     * Transform the internal Skill form option data into its Admin API representation.
     *
     * @param array $formOptions
     * @return array
     */
    public function transform(array $formOptions): array
    {
        return [
            'types' => array_map(
                fn (SkillTypeValue $skillType): int => $skillType->value,
                SkillTypeValue::cases(),
            ),
            'classes' => $formOptions['classes']->map(fn (GameClass $gameClass): array => [
                'id' => $gameClass->id,
                'name' => $gameClass->name,
            ])->values()->all(),
        ];
    }
}
