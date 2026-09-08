<?php

namespace App\Game\ClassRanks\Transformers;

use App\Flare\Models\GameClass;

class ClassDetailTransformer
{
    /**
     * Transform a Class into its reusable factual detail representation.
     */
    public function transform(GameClass $gameClass): array
    {
        return [
            'id' => $gameClass->id,
            'name' => $gameClass->name,
            'description' => $gameClass->description,
            'damage_stat' => $gameClass->damage_stat,
            'to_hit_stat' => $gameClass->to_hit_stat,
            'attributes' => [
                'str_mod' => $gameClass->str_mod,
                'dur_mod' => $gameClass->dur_mod,
                'dex_mod' => $gameClass->dex_mod,
                'chr_mod' => $gameClass->chr_mod,
                'int_mod' => $gameClass->int_mod,
                'agi_mod' => $gameClass->agi_mod,
                'focus_mod' => $gameClass->focus_mod,
            ],
            'combat_modifiers' => [
                'accuracy_mod' => $gameClass->accuracy_mod,
                'dodge_mod' => $gameClass->dodge_mod,
                'defense_mod' => $gameClass->defense_mod,
                'looting_mod' => $gameClass->looting_mod,
            ],
            'unlock_requirements' => $this->transformUnlockRequirements($gameClass),
        ];
    }

    /**
     * Build the unlock-requirement section, omitted entirely for a normal Class.
     */
    private function transformUnlockRequirements(GameClass $gameClass): ?array
    {
        if (is_null($gameClass->primary_required_class_id) || is_null($gameClass->secondary_required_class_id)) {
            return null;
        }

        return [
            'primary_required_class' => [
                'id' => $gameClass->primaryClassRequired->id,
                'name' => $gameClass->primaryClassRequired->name,
            ],
            'secondary_required_class' => [
                'id' => $gameClass->secondaryClassRequired->id,
                'name' => $gameClass->secondaryClassRequired->name,
            ],
            'primary_required_class_level' => $gameClass->primary_required_class_level,
            'secondary_required_class_level' => $gameClass->secondary_required_class_level,
        ];
    }
}
