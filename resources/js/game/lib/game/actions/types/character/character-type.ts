export interface CharacterType {
    affix_damage_reduction:  number;

    agi_modded: number;

    artifact_annulment: number;

    attack_types: any;

    base_stat: number;

    can_attack_again_at: string|null;

    chr_modded: number;

    class: string;

    devouring_darkness: number;

    devouring_darkness_res: number;

    devouring_light: number;

    devouring_light_res: number;

    dex_modded: number;

    dur_modded: number;

    extra_action_chance: any;

    focus_modded: number;

    healing_reduction: number;

    health: number;

    int_modded: number;

    is_alchemy_locked: boolean;

    is_attack_automation_locked: boolean;

    is_dead: boolean;

    name: string;

    resistance_reduction: number;

    skill_reduction: number;

    skills: any;

    spell_evasion: number;

    stat_affixes: any;

    str_modded: number;

    to_hit_base: number;

    voided_base_stat: number;

    voided_dex: number;

    voided_dur: number;

    voided_focus: number;

    voided_health: number;

    voided_to_hit_base: number;
}
