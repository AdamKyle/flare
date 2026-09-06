<?php

namespace App\Admin\Classes\Transformers;

use App\Flare\Models\GameClass;

class ClassDetailTransformer
{
    /**
     * Transform a Class into its Admin detail representation.
     *
     * @param  GameClass  $gameClass  Class to transform.
     * @return array{id: int, name: string, description: string|null, damage_stat: string, to_hit_stat: string, attributes: array{str_mod: int, dur_mod: int, dex_mod: int, chr_mod: int, int_mod: int, agi_mod: int, focus_mod: int}, combat_modifiers: array{accuracy_mod: float, dodge_mod: float, defense_mod: float, looting_mod: float}, unlock_requirements: array{primary_required_class: array{id: int, name: string}, secondary_required_class: array{id: int, name: string}, primary_required_class_level: int, secondary_required_class_level: int}|null} Admin Class detail representation.
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
     *
     * @param  GameClass  $gameClass  Class to describe.
     * @return array{primary_required_class: array{id: int, name: string}, secondary_required_class: array{id: int, name: string}, primary_required_class_level: int, secondary_required_class_level: int}|null Unlock-requirement section.
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
