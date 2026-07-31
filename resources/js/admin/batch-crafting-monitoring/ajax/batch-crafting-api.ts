import axios from "axios";
import {
    ActiveBatchCrafter,
    BatchCraftingChartPoint,
    BatchCraftingFilters,
    BatchCraftingRunRow,
    BatchCraftingSummary,
    Paginated,
} from "../types/batch-crafting-monitoring";
import BatchCraftingLogsPage from "../types/batch-crafting-logs-page";
import { handleUnauthenticatedAxiosRequest } from "../../../game/lib/ajax/unauthenticated-response-handler";

const base = "/api/admin/monitoring/batch-crafting";

export async function fetchBatchCraftingActive(): Promise<
    ActiveBatchCrafter[]
> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ActiveBatchCrafter[]>(`${base}/active`),
        )
    ).data;
}

export async function fetchBatchCraftingRuns(
    filters: BatchCraftingFilters,
    page: number,
): Promise<Paginated<BatchCraftingRunRow>> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Paginated<BatchCraftingRunRow>>(`${base}/runs`, {
                params: { ...filters, page },
            }),
        )
    ).data;
}

export async function fetchBatchCraftingSummary(
    days: string,
): Promise<BatchCraftingSummary> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<BatchCraftingSummary>(`${base}/summary`, {
                params: { days },
            }),
        )
    ).data;
}

export async function fetchBatchCraftingChart(
    days: string,
): Promise<BatchCraftingChartPoint[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<BatchCraftingChartPoint[]>(`${base}/chart`, {
                params: { days },
            }),
        )
    ).data;
}

export async function fetchBatchCraftingLogs(
    page: number,
    severity: string,
): Promise<BatchCraftingLogsPage> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<BatchCraftingLogsPage>(
                "/api/admin/monitoring/logs/entries",
                {
                    params: { file: "batch_crafting", page, severity },
                },
            ),
        )
    ).data;
}
