export interface CharacterType {
    affix_damage_reduction:  number;

    agi_modded: string;

    chr_modded: string;

    dex_modded: string;

    dur_modded: string;

    str_modded: string;

    focus_modded: string;

    int_modded: string;

    artifact_annulment: number;

    attack_types: any;

    base_stat: string;

    attack: string;

    ac: string;

    gold: string;

    copper_coins: string;

    gold_dust: string;

    shards: string;

    can_attack_again_at: string|null;

    race: string;

    class: string;

    devouring_darkness: number;

    devouring_darkness_res: number;

    devouring_light: number;

    devouring_light_res: number;

    extra_action_chance: any;

    healing_reduction: number;

    health: string;

    is_alchemy_locked: boolean;

    is_attack_automation_locked: boolean;

    is_dead: boolean;

    name: string;

    resistance_reduction: number;

    skill_reduction: number;

    skills: any;

    spell_evasion: number;

    stat_affixes: any;

    to_hit_base: number;

    voided_base_stat: number;

    voided_dex: number;

    voided_dur: number;

    voided_focus: number;

    voided_health: number;

    voided_to_hit_base: number;

    xp: number,

    xp_next: number,

    level: number;

    max_level: number;

}
