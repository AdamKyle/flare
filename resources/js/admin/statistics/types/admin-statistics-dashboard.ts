export interface ChartPoint {
    label: string;
    value: number;
}

export interface ChartSeries {
    label: string;
    points: ChartPoint[];
}

export interface ChartPayload {
    source: string;
    unit: string;
    points?: ChartPoint[];
    series?: ChartSeries[];
}

export interface CharacterSummary {
    id: number;
    name: string;
    gold?: number;
    level?: number;
}

export interface DashboardSummary {
    total_registered_users: number;
    total_characters: number;
    average_character_level: number;
    average_character_gold: number;
    richest_character: CharacterSummary | null;
    highest_level_character: CharacterSummary | null;
    completed_quests: number;
    completed_guide_quests: number;
}

export interface OnlineCharacter {
    character_name: string;
    user_name: string;
    level: number;
    map: string | null;
    logged_in_at: string | null;
    last_activity: string | null;
    last_heart_beat: string | null;
}

export interface KingdomSummary {
    total_kingdoms: number;
    kingdoms_with_owners: number;
    npc_kingdoms: number;
}

export interface KingdomHolder {
    character_name: string;
    kingdom_count: number;
}

export interface LoginParticipationSummary {
    label: string;
    window: string;
    distinct_login_users: number;
    total_users: number;
    percentage: number;
}

export interface InactiveUserDeletionSummary {
    today: number;
    last_30_days: number;
    all_time: number;
}

export interface AdminStatisticsDashboardSnapshot {
    generated_at: string;
    summary: DashboardSummary;
    login_chart: ChartPayload;
    registration_chart: ChartPayload;
    login_duration_chart: ChartPayload;
    today_login_count_chart: ChartPayload;
    login_participation_summary: LoginParticipationSummary[];
    login_participation_chart: ChartPayload;
    inactive_user_deletion_summary: InactiveUserDeletionSummary;
    online_characters: OnlineCharacter[];
    reincarnation_chart: ChartPayload;
    quest_completion_chart: ChartPayload;
    guide_quest_completion_chart: ChartPayload;
    gold_chart: ChartPayload;
    kingdom_summary: KingdomSummary;
    top_kingdom_holders: KingdomHolder[];
    metric_definitions: Record<string, string>;
}
