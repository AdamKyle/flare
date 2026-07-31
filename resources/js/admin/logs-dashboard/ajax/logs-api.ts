import axios from "axios";
import { handleUnauthenticatedAxiosRequest } from "../../../game/lib/ajax/unauthenticated-response-handler";
import {
    LogEntriesPage,
    LogEntry,
    LogFileInfo,
    LogFilters,
    LogsPollResponse,
    SystemBugReport,
} from "../types/logs-dashboard";

const base = "/api/admin/monitoring/logs";

export async function fetchLogFiles(): Promise<LogFileInfo[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<LogFileInfo[]>(`${base}/files`),
        )
    ).data;
}

export async function fetchLogEntryDetail(
    fileKey: string,
    detailId: string,
): Promise<LogEntry> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<LogEntry>(`${base}/entry-detail`, {
                params: { file: fileKey, detail_id: detailId },
            }),
        )
    ).data;
}

export async function fetchLogEntries(
    fileKey: string,
    filters: LogFilters,
    page: number,
    cursor?: string | null,
): Promise<LogEntriesPage> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<LogEntriesPage>(`${base}/entries`, {
                params: { file: fileKey, ...filters, page, cursor },
            }),
        )
    ).data;
}

export async function pollLogs(
    fileKey: string,
    filters: LogFilters,
): Promise<LogsPollResponse> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<LogsPollResponse>(`${base}/poll`, {
                params: { file: fileKey, ...filters },
            }),
        )
    ).data;
}

export async function fetchSystemBugs(): Promise<SystemBugReport[]> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<SystemBugReport[]>(`${base}/bugs`),
        )
    ).data;
}

export async function fetchBugChart(
    days: number,
): Promise<Array<{ period: string; occurrences: number }>> {
    return (
        await handleUnauthenticatedAxiosRequest(
            axios.get<Array<{ period: string; occurrences: number }>>(
                `${base}/bug-chart`,
                { params: { days } },
            ),
        )
    ).data;
}
