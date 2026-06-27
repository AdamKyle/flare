export type ActiveExplorer = {
  character_id: number;
  character_name: string | null;
  monster_name: string | null;
  attack_type: string;
  started_at: string | null;
  completed_at: string | null;
};

export type ExplorationLogRow = {
  id: number;
  character_id: number;
  character?: { name: string };
  monster_id: number | null;
  attack_type: string;
  starting_level: number;
  started_at: string | null;
  ended_at: string | null;
  stopped_reason: string | null;
  stopped_by_player: boolean;
  fights: number;
  kills: number;
  xp_gained: number;
  skill_xp_gained: number;
};

export type ExplorationSummary = {
  total_runs: number;
  stopped_by_player: number;
  total_kills: number;
  total_xp_gained: number;
  total_skill_xp_gained: number;
};

export type ExplorationChartPoint = {
  period: string;
  runs: number;
  kills: number;
  xp: number;
  skill_xp: number;
  active: number;
  completed: number;
};

export type ExplorationFilters = {
  character_name: string;
  stopped_reason: string;
  stopped_by_player: boolean;
  date_from: string;
  date_to: string;
  days: string;
};
