import {
    ActiveDelveRunner,
    DelveChartPoint,
    DelveFilters,
    DelveRunRow,
    DelveSummary,
    Paginated,
} from "./delve-monitoring";

export default interface DelveDashboardState {
    loading: boolean;
    error: string;
    active: ActiveDelveRunner[];
    runs: Paginated<DelveRunRow>;
    summary: DelveSummary;
    chart: DelveChartPoint[];
    filters: DelveFilters;
    page: number;
    days: string;
}
