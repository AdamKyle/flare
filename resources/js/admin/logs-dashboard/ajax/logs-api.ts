import axios from "axios";
import {
    LogEntriesPage,
    LogFileInfo,
    LogFilters,
    LogSummary,
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
