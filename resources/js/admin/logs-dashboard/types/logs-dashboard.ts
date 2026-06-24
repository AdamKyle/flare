export type LogFileInfo = {
    key: string;
    label: string;
    exists: boolean;
    size_bytes: number;
    files?: string[];
};

export type LogEntry = {
    timestamp: string | null;
    channel: string | null;
    severity: string;
    message: string;
    context: string | null;
    exception_class: string | null;
    exception_file: string | null;
    exception_line: number | null;
    stack_trace: string | null;
    raw_log_entry: string | null;
    file_path: string | null;
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

export type SystemBugOccurrence = {
    occurred_at: string | null;
    level: string | null;
    channel: string | null;
    file_path: string | null;
    message: string | null;
    exception_class: string | null;
    exception_file: string | null;
    exception_line: number | null;
    stack_trace: string | null;
    raw_log_entry: string | null;
    context: Record<string, unknown> | null;
};

export type SystemBugReport = {
    id: number;
    fingerprint: string | null;
    title: string;
    status: string;
    severity: string | null;
    first_seen_at: string | null;
    last_seen_at: string | null;
    occurrence_count: number;
    latest_message: string | null;
    latest_stack_trace: string | null;
    latest_raw_log_entry: string | null;
    occurrences: SystemBugOccurrence[];
};

export type LogsPollResponse = {
    entries: LogEntry[];
    summary: LogSummary;
    files: LogFileInfo[];
    bugs: SystemBugReport[];
    bug_chart: Array<{ period: string; occurrences: number }>;
};

export const SEVERITIES = [
    "",
    "emergency",
    "alert",
    "critical",
    "error",
    "fatal",
    "warning",
    "notice",
    "info",
    "debug",
] as const;
