export interface OutcomeCounts {
  survived: number;
  died: number;
  timeout: number;
  error: number;
}

export interface ActiveDelveRunner {
  character_id: number;
  character_name: string | null;
  started_at: string | null;
  increase_enemy_strength: number | null;
  increase_percentage: number | null;
  outcome_counts: OutcomeCounts;
  total_encounters: number;
  avg_pack_size: number | null;
}

export interface DelveLogEntry {
  id: number;
  pack_size: number;
  outcome: string;
  increased_enemy_strength: number | null;
}

export interface DelveRunRow {
  id: number;
  character_id: number;
  character?: { name: string };
  increase_enemy_strength: number | null;
  started_at: string | null;
  completed_at: string | null;
  delve_logs?: DelveLogEntry[];
}

export interface DelveSummary {
  total_runs: number;
  active: number;
  completed: number;
  total_survived: number;
  total_died: number;
  total_timeout: number;
}

export interface DelveChartPoint {
  period: string;
  runs: number;
  active: number;
  completed: number;
  survived: number;
  died: number;
  timeout: number;
}

export interface DelveFilters {
  character_name: string;
  date_from: string;
  date_to: string;
  status: string;
  outcome: string;
}
