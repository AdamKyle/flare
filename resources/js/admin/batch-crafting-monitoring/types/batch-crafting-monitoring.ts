export type ActiveBatchCrafter = {
    character_id: number;
    character_name: string | null;
    batch_type: string;
    disposition: string;
    started_at: string | null;
    ends_at: string | null;
    crafted_count: number;
    failed_count: number;
    kept_count: number;
    sold_count: number;
};

export type BatchCraftingRunRow = {
    id: number;
    character_id: number;
    character?: { name: string };
    batch_type: string;
    disposition: string;
    started_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    ended_reason: string | null;
    crafted_count: number;
    failed_count: number;
    kept_count: number;
    sold_count: number;
};

export type BatchCraftingSummary = {
    total_runs: number;
    active: number;
    completed: number;
    cancelled: number;
    total_crafted: number;
    total_failed: number;
};

export type BatchCraftingChartPoint = {
    period: string;
    runs: number;
    crafted: number;
    failed: number;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

export type BatchCraftingFilters = {
    character_name: string;
    date_from: string;
    date_to: string;
    status: string;
    batch_type: string;
};
