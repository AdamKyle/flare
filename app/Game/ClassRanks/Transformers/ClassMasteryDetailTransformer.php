<?php

namespace App\Game\ClassRanks\Transformers;

use App\Flare\Models\GameClassSpecial;

class ClassMasteryDetailTransformer
{
    /**
     * Transform a Class Mastery into its reusable factual detail representation.
     */
    public function transform(GameClassSpecial $gameClassSpecial): array
    {
        return [
            'id' => $gameClassSpecial->id,
            'name' => $gameClassSpecial->name,
            'description' => $gameClassSpecial->description,
            'game_class' => [
                'id' => $gameClassSpecial->gameClass->id,
                'name' => $gameClassSpecial->gameClass->name,
            ],
            'type' => $gameClassSpecial->specialty_damage > 0 ? 'attack' : 'passive',
            'requires_class_rank_level' => $gameClassSpecial->requires_class_rank_level,
            'attack' => [
                'specialty_damage' => $gameClassSpecial->specialty_damage,
                'increase_specialty_damage_per_level' => $gameClassSpecial->increase_specialty_damage_per_level,
                'specialty_damage_uses_damage_stat_amount' => $gameClassSpecial->specialty_damage_uses_damage_stat_amount,
                'attack_type_required' => $gameClassSpecial->attack_type_required,
            ],
            'modifiers' => [
                'base_damage_mod' => $gameClassSpecial->base_damage_mod,
                'base_ac_mod' => $gameClassSpecial->base_ac_mod,
                'base_healing_mod' => $gameClassSpecial->base_healing_mod,
                'base_spell_damage_mod' => $gameClassSpecial->base_spell_damage_mod,
                'health_mod' => $gameClassSpecial->health_mod,
                'base_damage_stat_increase' => $gameClassSpecial->base_damage_stat_increase,
            ],
            'evasion_and_reductions' => [
                'spell_evasion' => $gameClassSpecial->spell_evasion,
                'affix_damage_reduction' => $gameClassSpecial->affix_damage_reduction,
                'healing_reduction' => $gameClassSpecial->healing_reduction,
                'skill_reduction' => $gameClassSpecial->skill_reduction,
                'resistance_reduction' => $gameClassSpecial->resistance_reduction,
            ],
        ];
    }
}
