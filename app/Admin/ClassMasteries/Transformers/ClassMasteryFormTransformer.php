<?php

namespace App\Admin\ClassMasteries\Transformers;

use App\Flare\Models\GameClassSpecial;

class ClassMasteryFormTransformer
{
    /**
     * Transform a Class Mastery into its Admin save-response / form-value representation.
     *
     * @param  GameClassSpecial  $gameClassSpecial  Class Mastery to transform.
     * @return array{id: int, game_class_id: int, name: string, description: string|null, requires_class_rank_level: int, specialty_damage: int|null, increase_specialty_damage_per_level: int|null, specialty_damage_uses_damage_stat_amount: float|null, attack_type_required: string|null, base_damage_mod: float|null, base_ac_mod: float|null, base_healing_mod: float|null, base_spell_damage_mod: float|null, health_mod: float|null, base_damage_stat_increase: float|null, spell_evasion: float|null, affix_damage_reduction: float|null, healing_reduction: float|null, skill_reduction: float|null, resistance_reduction: float|null} Admin Class Mastery form-value representation.
     */
    public function transform(GameClassSpecial $gameClassSpecial): array
    {
        return [
            'id' => $gameClassSpecial->id,
            'game_class_id' => $gameClassSpecial->game_class_id,
            'name' => $gameClassSpecial->name,
            'description' => $gameClassSpecial->description,
            'requires_class_rank_level' => $gameClassSpecial->requires_class_rank_level,
            'specialty_damage' => $gameClassSpecial->specialty_damage,
            'increase_specialty_damage_per_level' => $gameClassSpecial->increase_specialty_damage_per_level,
            'specialty_damage_uses_damage_stat_amount' => $gameClassSpecial->specialty_damage_uses_damage_stat_amount,
            'attack_type_required' => $gameClassSpecial->attack_type_required,
            'base_damage_mod' => $gameClassSpecial->base_damage_mod,
            'base_ac_mod' => $gameClassSpecial->base_ac_mod,
            'base_healing_mod' => $gameClassSpecial->base_healing_mod,
            'base_spell_damage_mod' => $gameClassSpecial->base_spell_damage_mod,
            'health_mod' => $gameClassSpecial->health_mod,
            'base_damage_stat_increase' => $gameClassSpecial->base_damage_stat_increase,
            'spell_evasion' => $gameClassSpecial->spell_evasion,
            'affix_damage_reduction' => $gameClassSpecial->affix_damage_reduction,
            'healing_reduction' => $gameClassSpecial->healing_reduction,
            'skill_reduction' => $gameClassSpecial->skill_reduction,
            'resistance_reduction' => $gameClassSpecial->resistance_reduction,
        ];
    }
}
