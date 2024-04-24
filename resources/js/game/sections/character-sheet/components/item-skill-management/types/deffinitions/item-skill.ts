export default interface ItemSkill {
    name: string;
    description: string;
    agi_mod: number;
    base_ac_mod: number;
    base_attack_mod: number;
    base_healing_mod: number;
    chr_mod: number;
    current_kill: number;
    current_level: number;
    dex_mod: number;
    dur_mod: number;
    focus_mod: number;
    id: number;
    int_mod: number;
    str_mod: number;
    total_kills_needed: number;
    max_level: number;
    children: ItemSkill[] | [];
    parent_id: number | null;
    parent_level_needed: number | null;
}
