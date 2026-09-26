export interface DelveCurrentFoeStatsDefinition {
  str?: number;
  dur?: number;
  dex?: number;
  chr?: number;
  int?: number;
  agi?: number;
  focus?: number;
  ac?: number;
  health_range?: string | null;
  attack_range?: string | null;
  max_spell_damage?: number | null;
  healing_percentage?: number | null;
  max_level?: number | null;
  xp?: number;
  gold?: number;
}

export default interface DelveCurrentFoeDefinition {
  id: number | null;
  name: string | null;
  pack_size: number;
  enemy_strength_boost: number;
  stats_available: boolean;
  stats: DelveCurrentFoeStatsDefinition | [];
  source: string;
  message: string;
}
