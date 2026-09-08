<?php

namespace App\Admin\Classes\Transformers;

use App\Flare\Models\GameClass;

class ClassFormTransformer
{
    /**
     * Transform a Class into its Admin save-response / form-value representation.
     */
    public function transform(GameClass $gameClass): array
    {
        return [
            'id' => $gameClass->id,
            'name' => $gameClass->name,
            'description' => $gameClass->description,
            'damage_stat' => $gameClass->damage_stat,
            'to_hit_stat' => $gameClass->to_hit_stat,
            'str_mod' => $gameClass->str_mod,
            'dur_mod' => $gameClass->dur_mod,
            'dex_mod' => $gameClass->dex_mod,
            'chr_mod' => $gameClass->chr_mod,
            'int_mod' => $gameClass->int_mod,
            'agi_mod' => $gameClass->agi_mod,
            'focus_mod' => $gameClass->focus_mod,
            'accuracy_mod' => $gameClass->accuracy_mod,
            'dodge_mod' => $gameClass->dodge_mod,
            'defense_mod' => $gameClass->defense_mod,
            'looting_mod' => $gameClass->looting_mod,
            'primary_required_class_id' => $gameClass->primary_required_class_id,
            'secondary_required_class_id' => $gameClass->secondary_required_class_id,
            'primary_required_class_level' => $gameClass->primary_required_class_level,
            'secondary_required_class_level' => $gameClass->secondary_required_class_level,
        ];
    }
}
