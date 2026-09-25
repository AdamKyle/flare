import ItemSkillDefinition from './item-skill-definition';

export default interface ItemSkillProgressionDefinition {
  id: number;
  item_id: number;
  item_skill_id: number;
  current_level: number;
  current_kill: number;
  is_training: boolean;
  str_mod: number;
  dex_mod: number;
  dur_mod: number;
  chr_mod: number;
  focus_mod: number;
  int_mod: number;
  agi_mod: number;
  base_damage_mod: number;
  base_ac_mod: number;
  base_healing_mod: number;
  item_skill: ItemSkillDefinition;
}
