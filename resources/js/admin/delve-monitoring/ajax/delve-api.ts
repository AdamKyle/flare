import axios from "axios";
import {
    ActiveDelveRunner,
    DelveChartPoint,
    DelveFilters,
    DelveRunRow,
    DelveSummary,
    Paginated,
} from "../types/delve-monitoring";

const base = "/api/admin/monitoring/delve";

export async function fetchDelveActive(): Promise<ActiveDelveRunner[]> {
    return (await axios.get<ActiveDelveRunner[]>(`${base}/active`)).data;
}

export async function fetchDelveRuns(
    filters: DelveFilters,
    page: number,
): Promise<Paginated<DelveRunRow>> {
    return (
        await axios.get<Paginated<DelveRunRow>>(`${base}/runs`, {
            params: { ...filters, page },
        })
    ).data;
}

export async function fetchDelveSummary(days: string): Promise<DelveSummary> {
    return (
        await axios.get<DelveSummary>(`${base}/summary`, { params: { days } })
    ).data;
}

export async function fetchDelveChart(
    days: string,
): Promise<DelveChartPoint[]> {
    return (
        await axios.get<DelveChartPoint[]>(`${base}/chart`, {
            params: { days },
        })
    ).data;
}
