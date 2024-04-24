export default interface ClassSpecialtiesType {
    base_ac_mod: number | null;

    base_damage_mod: number | null;

    base_damage_stat_increase: number | null;

    base_healing_mod: number | null;

    base_spell_damage_mod: number | null;

    description: string;

    game_class_id: number;

    health_mod: number | null;

    id: number;

    increase_specialty_damage_per_level: number | null;

    name: string;

    requires_class_rank_level: number;

    specialty_damage: number | null;

    specialty_damage_uses_damage_stat_amount: number | null;

    attack_type_required: string;

    class_name: string;

    spell_evasion: number;

    affix_damage_reduction: number;

    healing_reduction: number;

    skill_reduction: number;

    resistance_reduction: number;
}
