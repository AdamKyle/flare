export default interface ClassFormDefinition {
  id: number;
  name: string;
  description: string | null;
  damage_stat: string;
  to_hit_stat: string;
  str_mod: number;
  dur_mod: number;
  dex_mod: number;
  chr_mod: number;
  int_mod: number;
  agi_mod: number;
  focus_mod: number;
  accuracy_mod: number;
  dodge_mod: number;
  defense_mod: number;
  looting_mod: number;
  primary_required_class_id: number | null;
  secondary_required_class_id: number | null;
  primary_required_class_level: number | null;
  secondary_required_class_level: number | null;
}
