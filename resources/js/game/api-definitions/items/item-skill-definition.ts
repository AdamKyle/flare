export default interface ItemSkillDefinition {
  id: number;
  name: string;
  description: string;
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
  max_level: number;
  total_kills_needed: number;
  parent_id: number | null;
  parent_level_needed: number | null;
  children: ItemSkillDefinition[];
}
