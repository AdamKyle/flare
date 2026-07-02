import {
    ActiveBatchCrafter,
    BatchCraftingChartPoint,
    BatchCraftingFilters,
    BatchCraftingRunRow,
    BatchCraftingSummary,
    Paginated,
} from "./batch-crafting-monitoring";
import BatchCraftingLogsPage from "./batch-crafting-logs-page";

export default interface BatchCraftingDashboardState {
    loading: boolean;
    error: string | null;
    days: string;
    summary: BatchCraftingSummary;
    chartData: BatchCraftingChartPoint[];
    active: ActiveBatchCrafter[];
    runs: Paginated<BatchCraftingRunRow>;
    filters: BatchCraftingFilters;
    page: number;
    logs: BatchCraftingLogsPage;
    logPage: number;
    logSeverity: string;
}
