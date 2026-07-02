import {
    LogEntriesPage,
    LogEntry,
    LogFileInfo,
    LogFilters,
    LogSummary,
    SystemBugReport,
} from "./logs-dashboard";

export default interface LogsDashboardState {
    loading: boolean;
    error: string;
    files: LogFileInfo[];
    selectedFile: string;
    filters: LogFilters;
    page: number;
    entries: LogEntriesPage;
    summary: LogSummary;
    newEntries: LogEntry[];
    bugs: SystemBugReport[];
    bugChart: Array<{ period: string; occurrences: number }>;
    bugRange: number;
    selectedEntry: LogEntry | null;
    selectedBug: SystemBugReport | null;
}
