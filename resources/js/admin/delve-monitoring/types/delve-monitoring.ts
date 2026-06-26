export type OutcomeCounts = {
    survived: number;
    died: number;
    timeout: number;
    error: number;
};

export type ActiveDelveRunner = {
    character_id: number;
    character_name: string | null;
    started_at: string | null;
    increase_enemy_strength: number | null;
    increase_percentage: number | null;
    outcome_counts: OutcomeCounts;
    total_encounters: number;
    avg_pack_size: number | null;
};

export type DelveLogEntry = {
    id: number;
    pack_size: number;
    outcome: string;
    increased_enemy_strength: number | null;
};

export type DelveRunRow = {
    id: number;
    character_id: number;
    character?: { name: string };
    increase_enemy_strength: number | null;
    started_at: string | null;
    completed_at: string | null;
    delve_logs?: DelveLogEntry[];
};

export type DelveSummary = {
    total_runs: number;
    active: number;
    completed: number;
    total_survived: number;
    total_died: number;
    total_timeout: number;
};

export type DelveChartPoint = {
    period: string;
    runs: number;
    active: number;
    completed: number;
    survived: number;
    died: number;
    timeout: number;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

export type DelveFilters = {
    character_name: string;
    date_from: string;
    date_to: string;
    status: string;
    outcome: string;
};
