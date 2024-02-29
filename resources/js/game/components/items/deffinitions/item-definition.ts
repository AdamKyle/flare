import ItemAtonement from "./item-atonement";
import {ItemType} from "../enums/item-type";

export default interface ItemDefinition {
    affects_skills: any[]; // or specify the type if known
    affix_count: number;
    affix_damage_reduction: number;
    agi_modifier: number;
    ambush_chance: number;
    ambush_resistance: number;
    ambush_resistance_chance: number;
    applied_stacks: any[]; // or specify the type if known
    raw_damage: number;
    raw_ac: number;
    raw_healing: number;
    base_ac: number;
    base_ac_mod: any; // specify the type if known
    base_ac_mod_bonus: number;
    base_damage: number;
    base_damage_mod: number;
    base_damage_mod_bonus: number;
    base_healing: number;
    base_healing_mod: any; // specify the type if known
    base_healing_mod_bonus: number;
    can_resurrect: boolean;
    can_use_on_other_items: boolean;
    chr_modifier: number;
    cost: number;
    counter_chance: number;
    counter_resistance: number;
    counter_resistance_chance: number;
    crafting_type: string;
    damages_kingdoms: boolean;
    default_position: string;
    description: string;
    devouring_darkness: number;
    devouring_light: number;
    dex_modifier: number;
    dur_modifier: number;
    fight_time_out_mod_bonus: number;
    focus_modifier: number;
    healing_reduction: number;
    holy_level: any; // specify the type if known
    holy_stack_devouring_darkness: number;
    holy_stack_stat_bonus: number;
    holy_stacks: number;
    holy_stacks_applied: number;
    id: number;
    ignores_caps: boolean;
    increase_skill_bonus_by: number;
    increase_skill_training_bonus_by: number;
    increase_stat_by: number;
    int_modifier: number;
    is_mythic: boolean;
    is_unique: boolean;
    item_atonements:ItemAtonement;
    item_prefix: any; // specify the type if known
    item_suffix: any; // specify the type if known
    kingdom_damage: number;
    lasts_for: any; // specify the type if known
    min_cost: number;
    move_time_out_mod_bonus: number;
    name: string;
    resurrection_chance: number;
    skill_bonus: any; // specify the type if known
    skill_level_req: number;
    skill_level_trivial: number;
    skill_name: any; // specify the type if known
    skill_training_bonus: any; // specify the type if known
    socket_amount: number;
    sockets: any[]; // or specify the type if known
    spell_evasion: number;
    stat_increase: boolean;
    str_modifier: number;
    type: ItemType;
    usable: boolean;
    xp_bonus: any; // specify the type if known
}
