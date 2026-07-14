export interface SkillBonusItem {
    name: string;
    type: string;
    position: string;
    affix_count: number;
    is_unique: boolean;
    is_mythic: boolean;
    is_cosmic: boolean;
    holy_stacks_applied: number;
    skill_bonus?: number;
    skill_training_bonus?: number;
}

export default interface SkillDetails {
    id: number;
    name: string;
    description: string;
    skill_bonus: number;
    skill_xp_bonus: number;
    xp: number;
    xp_max: number;
    level: number;
    max_level: number;
    xp_towards: number | null;
    is_locked: boolean;
    unit_time_reduction: number;
    building_time_reduction: number;
    unit_movement_time_reduction: number;
    base_damage_mod: number;
    base_healing_mod: number;
    base_ac_mod: number;
    fight_timeout_mod: number;
    move_timeout_mod: number;
    class_bonus: number;
    skill_bonus_break_down: SkillBonusItem[];
    skill_xp_bonus_break_down: SkillBonusItem[];
}
