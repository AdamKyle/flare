import axios from "axios";
import {
    LogEntriesPage,
    LogFileInfo,
    LogFilters,
    LogSummary,
    LogsPollResponse,
    SystemBugReport,
} from "../types/logs-dashboard";

const base = "/api/admin/monitoring/logs";

export async function fetchLogFiles(): Promise<LogFileInfo[]> {
    return (await axios.get<LogFileInfo[]>(`${base}/files`)).data;
}

export async function fetchLogEntries(
    fileKey: string,
    filters: LogFilters,
    page: number,
): Promise<LogEntriesPage> {
    return (
        await axios.get<LogEntriesPage>(`${base}/entries`, {
            params: { file: fileKey, ...filters, page },
        })
    ).data;
}

export async function fetchLogSummary(
    fileKey: string,
    filters: LogFilters,
): Promise<LogSummary> {
    return (
        await axios.get<LogSummary>(`${base}/summary`, {
            params: { file: fileKey, ...filters },
        })
    ).data;
}

export async function pollLogs(
    fileKey: string,
    filters: LogFilters,
): Promise<LogsPollResponse> {
    return (
        await axios.get<LogsPollResponse>(`${base}/poll`, {
            params: { file: fileKey, ...filters },
        })
    ).data;
}

export async function fetchSystemBugs(): Promise<SystemBugReport[]> {
    return (await axios.get<SystemBugReport[]>(`${base}/bugs`)).data;
}

export async function fetchBugChart(
    days: number,
): Promise<Array<{ period: string; occurrences: number }>> {
    return (
        await axios.get<Array<{ period: string; occurrences: number }>>(
            `${base}/bug-chart`,
            { params: { days } },
        )
    ).data;
}
