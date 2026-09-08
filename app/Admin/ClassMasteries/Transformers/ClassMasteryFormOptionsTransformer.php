<?php

namespace App\Admin\ClassMasteries\Transformers;

use App\Flare\Models\GameClass;
use App\Game\Core\Combat\Values\AttackType;

class ClassMasteryFormOptionsTransformer
{
    /**
     * Transform the supplied internal Class Mastery form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'classes' => $formOptions['classes']->map(fn (GameClass $gameClass): array => [
                'id' => $gameClass->id,
                'name' => $gameClass->name,
            ])->values()->all(),
            'attack_types' => [
                ...array_map(fn (AttackType $attackType): string => $attackType->value, AttackType::cases()),
                'any',
            ],
        ];
    }
}
