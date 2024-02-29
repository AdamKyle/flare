import {ItemType} from "../../items/enums/item-type";

export interface attachedSkills {
    skill_name: string,
    skill_training_bonus: number,
    skill_bonus: number
}

export default interface ComparisonDetails {
    [key:string]: any;
    damage_adjustment: number,
    base_damage_adjustment: number,
    base_damage_mod_adjustment: number,
    ac_adjustment: number,
    base_ac_adjustment: number,
    healing_adjustment: number,
    base_healing_adjustment: number,
    str_adjustment: number,
    dur_adjustment: number,
    dex_adjustment: number,
    chr_adjustment: number,
    int_adjustment: number,
    agi_adjustment: number,
    focus_adjustment: number,
    fight_time_out_mod_adjustment: number,
    spell_evasion_adjustment: number,
    res_chance_adjustment: number,
    ambush_chance_adjustment: number,
    ambush_resistance_adjustment: number,
    counter_chance_adjustment: number,
    counter_resistance_adjustment: number,
    str_reduction: number,
    dur_reduction: number,
    dex_reduction: number,
    chr_reduction: number,
    int_reduction: number,
    agi_reduction: number,
    focus_reduction: number,
    reduces_enemy_stats: number,
    steal_life_amount: number,
    entranced_chance: number,
    damage: number,
    class_bonus: number,
    name: string,
    skills: attachedSkills[]|[],
    position: string,
    is_unique: boolean,
    affix_count: number,
    holy_stacks_applied: number,
    type:ItemType,
    is_mythic: boolean;
}
