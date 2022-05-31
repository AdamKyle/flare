export default interface InventoryComparisonAdjustment {

    [index: string]: number|string|boolean;

    affix_name: string;

    type: string;

    affix_count: number;

    is_unique: boolean;

    is_mythic: boolean;

    holy_stacks_applied: number;

    damage_adjustment: number;

    ac_adjustment: number;

    healing_adjustment: number;

    spell_evasion_adjustment: number;

    artifact_annulment_adjustment: number;

    res_chance_adjustment: number;

    base_damage_adjustment: number;

    base_healing_adjustment: number;

    base_ac_adjustment: number;

    fight_time_out_mod_adjustment: number;

    base_damage_mod_adjustment: number;

    str_adjustment: number;

    dur_adjustment: number;

    dex_adjustment: number;

    chr_adjustment: number;

    int_adjustment: number;

    agi_adjustment: number;

    focus_adjustment: number;

    ambush_chance_adjustment: number;

    ambush_resistance_adjustment: number;

    counter_chance_adjustment: number;

    counter_resistance_adjustment: number;

    cost: number;
}
