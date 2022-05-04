export default interface UsableItemsDetails {
    affects_skill_type: number;

    id: number;

    item_name: string;

    slot_id: number;

    item_id: number;

    type: string;

    agi_mod: number | 0;

    chr_mod: number | 0;

    dex_mod: number | 0;

    dur_mod: number | 0;

    focus_mod: number | 0;

    int_mod: number | 0;

    str_mod: number | 0;

    base_ac_mod: number | 0;

    base_ac_mod_bonus: number | 0;

    base_damage_mod: number | 0;

    base_damage_mod_bonus: number | 0;

    base_healing_mod: number | 0;

    base_healing_mod_bonus: number | 0;

    damages_kingdoms: boolean;

    description: string;

    fight_time_out_mod_bonus: number | 0;

    increase_skill_bonus_by: number | 0;

    increase_skill_training_bonus_by: number | 0;

    kingdom_damage:number | 0;

    lasts_for: number | 0;

    move_time_out_mod_bonus:number | 0;

    skills: string[] | [];

    usable: boolean;

    stat_increase: number;
}
