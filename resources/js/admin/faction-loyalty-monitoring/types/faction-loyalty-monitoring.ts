export type ActiveFactionLoyaltyRunner = {
  character_id: number;
  character_name: string | null;
  npc_name: string | null;
  last_action: string | null;
  last_action_at: string | null;
  started_at: string | null;
  last_fight_outcome: string | null;
  last_fight_was_bounty_target: boolean | null;
  failed_bounty_monster_name: string | null;
  failed_crafting_item_name: string | null;
};

export type FactionLoyaltyLog = {
  id: number;
  fight_logs: Record<string, unknown>[] | null;
  crafting_logs: Record<string, unknown>[] | null;
};

export type FactionLoyaltyRunRow = {
  id: number;
  character_id: number;
  character?: { name: string };
  faction_loyalty_npc_id: number | null;
  factionLoyaltyNpc?: { npc?: { name: string } };
  last_automation_action: string | null;
  last_automation_action_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  log?: FactionLoyaltyLog | null;
};

export type FactionLoyaltySummary = {
  total_runs: number;
  active: number;
  completed: number;
};

export type FactionLoyaltyChartPoint = {
  period: string;
  runs: number;
  active: number;
  completed: number;
};

export type FactionLoyaltyFilters = {
  character_name: string;
  date_from: string;
  date_to: string;
  status: string;
  days: string;
};
