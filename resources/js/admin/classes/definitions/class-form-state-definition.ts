export default interface ClassFormStateDefinition {
  name: string;
  description: string;
  damage_stat: string;
  to_hit_stat: string;
  str_mod: string;
  dur_mod: string;
  dex_mod: string;
  chr_mod: string;
  int_mod: string;
  agi_mod: string;
  focus_mod: string;
  accuracy_mod: string;
  dodge_mod: string;
  defense_mod: string;
  looting_mod: string;
  primary_required_class_id: number | null;
  secondary_required_class_id: number | null;
  primary_required_class_level: string;
  secondary_required_class_level: string;
}
