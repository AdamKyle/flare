export interface ReincarnationBaseInfoDefinition {
  base_damage_stat_mod: number;
  base_stat_mod: number;
  reincarnated_stat_increase: number;
  reincarnated_times: number;
  xp_penalty: number;
}

export interface ReincarnationInfoDefinition {
  data: ReincarnationBaseInfoDefinition;
}
