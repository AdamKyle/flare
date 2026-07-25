import axios from "axios";
import { handleUnauthenticatedAxiosRequest } from "../../../game/lib/ajax/unauthenticated-response-handler";
import {
    ActiveExplorer,
    ExplorationChartPoint,
    ExplorationFilters,
    ExplorationLogRow,
    ExplorationSummary,
    Paginated,
} from "../types/exploration-monitoring";

const base = "/api/admin/monitoring/exploration";

export async function fetchExplorationActive(): Promise<ActiveExplorer[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ActiveExplorer[]>(`${base}/active`),
        )
    ).data;
}

export async function fetchExplorationLogs(
    filters: ExplorationFilters,
    page: number,
): Promise<Paginated<ExplorationLogRow>> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Paginated<ExplorationLogRow>>(`${base}/logs`, {
                params: { ...filters, page },
            }),
        )
    ).data;
}

export async function fetchExplorationSummary(
    days: string,
): Promise<ExplorationSummary> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ExplorationSummary>(`${base}/summary`, {
                params: { days },
            }),
        )
    ).data;
}

export async function fetchExplorationChart(
    days: string,
): Promise<ExplorationChartPoint[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ExplorationChartPoint[]>(`${base}/chart`, {
                params: { days },
            }),
        )
    ).data;
}
