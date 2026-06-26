export type Summary = {
    queued: number;
    pending: number;
    processing: number;
    resumable: number;
    completed: number;
    failed: number;
};

export type ChartPoint = {
    period: string;
    pending: number;
    processing: number;
    resumable?: number;
    completed: number;
    failed: number;
};

export type CharacterRow = {
    character_id: number;
    character_name: string;
    battle_requests: number;
    quest_requests: number;
    pending_count: number;
    processing_count: number;
    resumable_count: number;
    failed_count: number;
    completed_count: number;
    last_request_at: string;
};

export type RewardRequest = {
    id: number;
    character?: { name: string };
    status: string;
    priority: string;
    source_type: string;
    source_id: string | null;
    failed_reason: string | null;
    created_at: string;
    updated_at: string;
    current_step_name?: string | null;
    current_step_status?: string | null;
    completed_step_count?: number;
    total_step_count?: number;
    un_emitted_message_count?: number;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
};

export type RequestFiltersType = {
    status: string;
    priority: string;
    source_type: string;
    date_from: string;
    date_to: string;
    character_name: string;
    failed_reason: string;
    source_id: string;
};

export type StaleQueue = {
    character_id: number;
    character_name: string;
    queue_state_id: number;
    started_at: string | null;
    heartbeat_at: string | null;
    stale_age_seconds: number | null;
    pending_request_count: number;
    processing_request_count: number;
    resumable_request_count: number;
    failed_request_count: number;
    current_request_id: number | null;
    current_request_source_type: string | null;
    current_request_source_id: string | null;
    current_ledger_step: string | null;
    current_ledger_step_status: string | null;
    current_ledger_step_heartbeat_at: string | null;
    checkpoint_age_seconds: number | null;
    un_emitted_message_count: number;
    oldest_pending_request_created_at: string | null;
    oldest_processing_request_created_at: string | null;
    requests: RewardRequest[];
};

export type RepairSummary = {
    repaired_queue_state_count: number;
    resumed_processing_request_count: number;
    legacy_failed_processing_request_count: number;
    restarted_processor_count: number;
    cleared_inactive_queue_state_count: number;
    resumable_step_count: number;
    un_emitted_message_count: number;
};

export type ChartsResponse = {
    last_hour: ChartPoint[];
    last_7_days: ChartPoint[];
    previous_7_days: ChartPoint[];
};

export type CharacterDetailResponse = {
    charts: Record<string, ChartPoint[]>;
    requests: Paginated<RewardRequest>;
};
