import axios from "axios";
import {
    ActiveFactionLoyaltyRunner,
    FactionLoyaltyChartPoint,
    FactionLoyaltyFilters,
    FactionLoyaltyRunRow,
    FactionLoyaltySummary,
    Paginated,
} from "../types/faction-loyalty-monitoring";

const base = "/api/admin/monitoring/faction-loyalty";

export async function fetchFactionLoyaltyActive(): Promise<
    ActiveFactionLoyaltyRunner[]
> {
    return (await axios.get<ActiveFactionLoyaltyRunner[]>(`${base}/active`))
        .data;
}

export async function fetchFactionLoyaltyRuns(
    filters: FactionLoyaltyFilters,
    page: number,
): Promise<Paginated<FactionLoyaltyRunRow>> {
    return (
        await axios.get<Paginated<FactionLoyaltyRunRow>>(`${base}/runs`, {
            params: { ...filters, page },
        })
    ).data;
}

export async function fetchFactionLoyaltySummary(
    days: string,
): Promise<FactionLoyaltySummary> {
    return (
        await axios.get<FactionLoyaltySummary>(`${base}/summary`, {
            params: { days },
        })
    ).data;
}

export async function fetchFactionLoyaltyChart(
    days: string,
): Promise<FactionLoyaltyChartPoint[]> {
    return (
        await axios.get<FactionLoyaltyChartPoint[]>(`${base}/chart`, {
            params: { days },
        })
    ).data;
}
