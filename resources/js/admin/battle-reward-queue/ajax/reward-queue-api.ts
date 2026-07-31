import axios from "axios";
import { handleUnauthenticatedAxiosRequest } from "../../../game/lib/ajax/unauthenticated-response-handler";
import {
    CharacterDetailResponse,
    CharacterRow,
    ChartPoint,
    ChartsResponse,
    Paginated,
    RepairSummary,
    RequestFiltersType,
    RewardRequest,
    StaleQueue,
    Summary,
} from "../types/reward-queue";

const baseUrl = "/api/admin/character-reward-queue";

export async function fetchRewardQueueSummary(): Promise<Summary> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Summary>(`${baseUrl}/summary`),
        )
    ).data;
}

export async function fetchRewardQueueCharts(): Promise<ChartsResponse> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ChartsResponse>(`${baseUrl}/charts`),
        )
    ).data;
}

export async function fetchRewardQueueCharacters(
    page: number,
): Promise<Paginated<CharacterRow>> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Paginated<CharacterRow>>(`${baseUrl}/characters`, {
                params: { page },
            }),
        )
    ).data;
}

export async function fetchRewardQueueRequests(
    filters: RequestFiltersType,
    page: number,
): Promise<Paginated<RewardRequest>> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Paginated<RewardRequest>>(`${baseUrl}/requests`, {
                params: { ...filters, page },
            }),
        )
    ).data;
}

export async function fetchCharacterRewardQueue(
    characterId: number,
    filters: RequestFiltersType,
    page: number,
): Promise<CharacterDetailResponse> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<CharacterDetailResponse>(
                `${baseUrl}/characters/${characterId}`,
                { params: { ...filters, page } },
            ),
        )
    ).data;
}

export async function fetchRewardQueueStatusVolume(
    days: string,
): Promise<ChartPoint[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<ChartPoint[]>(`${baseUrl}/status-breakdown`, {
                params: { days },
            }),
        )
    ).data;
}

export async function fetchStaleRewardQueues(): Promise<StaleQueue[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<StaleQueue[]>(`${baseUrl}/stale`),
        )
    ).data;
}

export async function repairStaleRewardQueues(): Promise<RepairSummary> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.post<RepairSummary>(`${baseUrl}/stale/repair`),
        )
    ).data;
}
