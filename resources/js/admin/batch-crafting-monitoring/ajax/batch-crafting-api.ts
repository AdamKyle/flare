import axios from "axios";
import {
    ActiveBatchCrafter,
    BatchCraftingChartPoint,
    BatchCraftingFilters,
    BatchCraftingRunRow,
    BatchCraftingSummary,
    Paginated,
} from "../types/batch-crafting-monitoring";

const base = "/api/admin/monitoring/batch-crafting";

export async function fetchBatchCraftingActive(): Promise<
    ActiveBatchCrafter[]
> {
    return (await axios.get<ActiveBatchCrafter[]>(`${base}/active`)).data;
}

export async function fetchBatchCraftingRuns(
    filters: BatchCraftingFilters,
    page: number,
): Promise<Paginated<BatchCraftingRunRow>> {
    return (
        await axios.get<Paginated<BatchCraftingRunRow>>(`${base}/runs`, {
            params: { ...filters, page },
        })
    ).data;
}

export async function fetchBatchCraftingSummary(
    days: string,
): Promise<BatchCraftingSummary> {
    return (
        await axios.get<BatchCraftingSummary>(`${base}/summary`, {
            params: { days },
        })
    ).data;
}

export async function fetchBatchCraftingChart(
    days: string,
): Promise<BatchCraftingChartPoint[]> {
    return (
        await axios.get<BatchCraftingChartPoint[]>(`${base}/chart`, {
            params: { days },
        })
    ).data;
}

export interface BatchCraftingLogEntry {
    date: string;
    env: string;
    level: string;
    level_class: string;
    level_img: string;
    text: string;
    header: string[];
    context: string[];
    stack: string;
    in_file: string;
}

export interface BatchCraftingLogsPage {
    data: BatchCraftingLogEntry[];
    current_page: number;
    last_page: number;
    total: number;
}

export async function fetchBatchCraftingLogs(
    page: number,
    severity: string,
): Promise<BatchCraftingLogsPage> {
    return (
        await axios.get<BatchCraftingLogsPage>(
            "/api/admin/monitoring/logs/entries",
            {
                params: { file: "batch_crafting", page, severity },
            },
        )
    ).data;
}
