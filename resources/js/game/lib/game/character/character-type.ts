export interface CharacterType {
    id: number;

    user_id: number;

    affix_damage_reduction:  number;

    agi_modded: number;

    chr_modded: number;

    dex_modded: number;

    dur_modded: number;

    str_modded: number;

    focus_modded: number;

    int_modded: number;

    agi: number;

    chr: number;

    dex: number;

    dur: number;

    str: number;

    focus: number;

    int: number;

    holy_bonus: number;

    current_stacks: number;

    max_holy_stacks: number;

    holy_attack_bonus: number;

    holy_ac_bonus: number;

    holy_healing_bonus: number;

    inventory_max: number;

    inventory_count: number;

    artifact_annulment: number;

    heal_for: number;

    attack_types: any;

    base_stat: number;

    attack: number;

    ac: number;

    gold: string;

    copper_coins: string;

    gold_dust: string;

    shards: string;

    damage_stat: string;

    to_hit_stat: string;

    can_attack_again_at: number|null;

    race: string;

    class: string;

    devouring_darkness: number;

    devouring_darkness_res: number;

    devouring_light: number;

    devouring_light_res: number;

    extra_action_chance: any;

    healing_reduction: number;

    health: number;

    is_alchemy_locked: boolean;

    is_attack_automation_locked: boolean;

    is_dead: boolean;

    can_attack: boolean;

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

    ambush_chance: number;

    ambush_resistance: number;

    counter_chance: number;

    counter_resistance: number;

    can_craft: boolean;

    can_adventure: boolean;

    can_craft_again_at: number|null;

    can_adventure_again_at: number|null;

    can_use_work_bench: boolean;

    is_silenced: boolean;

    can_talk_again_at: string;
}
