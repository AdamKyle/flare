export type LogFileInfo = {
    key: string;
    label: string;
    exists: boolean;
    size_bytes: number;
};

export type LogEntry = {
    timestamp: string | null;
    channel: string | null;
    severity: string;
    message: string;
    context: string | null;
    raw_parseable: boolean;
};

export type LogSummary = {
    total: number;
    by_severity: Record<string, number>;
    chart: Array<{ period: string; count: number }>;
};

export type LogEntriesPage = {
    data: LogEntry[];
    current_page: number;
    last_page: number;
    total: number;
};

export type LogFilters = {
    severity: string;
    date_from: string;
    date_to: string;
};

export const SEVERITIES = [
    "",
    "emergency",
    "alert",
    "critical",
    "error",
    "warning",
    "notice",
    "info",
    "debug",
] as const;
