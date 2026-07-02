import {
    CharacterRow,
    ChartPoint,
    ChartsResponse,
    Paginated,
    RequestFiltersType,
    RewardRequest,
    StaleQueue,
    Summary,
} from "./reward-queue";

export default interface RewardQueueDashboardState {
    summary: Summary;
    charts: ChartsResponse;
    characters: Paginated<CharacterRow>;
    requests: Paginated<RewardRequest>;
    selectedCharacter: CharacterRow | null;
    detailCharts: Record<string, ChartPoint[]>;
    globalChart: ChartPoint[];
    range: string;
    characterPage: number;
    requestPage: number;
    loading: boolean;
    error: string;
    message: string;
    showStaleView: boolean;
    filters: RequestFiltersType;
    staleQueues: StaleQueue[];
    repairing: boolean;
}
