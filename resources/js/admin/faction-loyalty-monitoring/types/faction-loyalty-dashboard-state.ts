import {
    ActiveFactionLoyaltyRunner,
    FactionLoyaltyChartPoint,
    FactionLoyaltyFilters,
    FactionLoyaltyRunRow,
    FactionLoyaltySummary,
    Paginated,
} from "./faction-loyalty-monitoring";

export default interface FactionLoyaltyDashboardState {
    loading: boolean;
    error: string;
    active: ActiveFactionLoyaltyRunner[];
    runs: Paginated<FactionLoyaltyRunRow>;
    summary: FactionLoyaltySummary;
    chart: FactionLoyaltyChartPoint[];
    filters: FactionLoyaltyFilters;
    page: number;
    days: string;
}
