import ItemSkill from "./item-skill";

export default interface ItemSkillProgression {
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
    is_training: boolean;
    item_id: number;
    item_skill_id: number;
    str_mod: number;
    item_skill: ItemSkill;
}
