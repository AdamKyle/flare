import {
    ActiveExplorer,
    ExplorationChartPoint,
    ExplorationFilters,
    ExplorationLogRow,
    ExplorationSummary,
    Paginated,
} from "./exploration-monitoring";

export default interface ExplorationDashboardState {
    loading: boolean;
    error: string;
    activeExplorers: ActiveExplorer[];
    logs: Paginated<ExplorationLogRow>;
    summary: ExplorationSummary;
    chart: ExplorationChartPoint[];
    filters: ExplorationFilters;
    logPage: number;
    days: string;
}
