import GemBagSlotDetails from "../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";

export default interface ItemToEquip {
    [key: string]: any;
    id: number,
    item_id: number,
    description: string,
    affix_name: string,
    base_damage: number,
    base_ac: number,
    base_healing: number,
    base_damage_mod: number,
    base_ac_mod: null,
    base_healing_mod: null,
    str_modifier: number,
    dur_modifier: number,
    int_modifier: number,
    dex_modifier: number,
    chr_modifier: number,
    agi_modifier: number,
    focus_modifier: number,
    type: string,
    default_position: string,
    crafting_type: string,
    skill_level_req: number,
    skill_level_trivial: number,
    cost: number,
    shop_cost: number,
    base_damage_mod_bonus: number,
    base_healing_mod_bonus: number,
    base_ac_mod_bonus: number,
    resurrection_chance: number,
    spell_evasion: number,
    artifact_annulment: number,
    is_unique: boolean,
    is_mythic: boolean,
    affix_count: number,
    min_cost: number,
    holy_level: number | null,
    holy_stacks: number,
    holy_stack_devouring_darkness: number,
    holy_stack_stat_bonus: number,
    holy_stacks_applied: number,
    ambush_chance: number,
    ambush_resistance_chance: number,
    counter_chance: number,
    counter_resistance_chance: number,
    str_reduction: number,
    dur_reduction: number,
    dex_reduction: number,
    chr_reduction: number,
    int_reduction: number,
    agi_reduction: number,
    focus_reduction: number,
    reduces_enemy_stats: number,
    resistance_reduction: number,
    steal_life_amount: number,
    entranced_chance: number,
    damage: number,
    class_bonus: number,
    skills: any[]|[];

    damages_kingdoms: boolean;
    kingdom_damage: number;
    can_stack: boolean;
    gain_additional_level: boolean;
    xp_bonus: number;
    lasts_for: number;
    move_time_out_mod_bonus: number;
    fight_time_out_mod_bonus: number;
    increase_skill_bonus_by: number;
    increase_skill_training_bonus_by: number;

    item: {
        gem: GemBagSlotDetails;
    }
}
