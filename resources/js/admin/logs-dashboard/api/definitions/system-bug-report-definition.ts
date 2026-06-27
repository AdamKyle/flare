import SystemBugOccurrenceDefinition from './system-bug-occurrence-definition';

export default interface SystemBugReportDefinition {
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
  occurrences: SystemBugOccurrenceDefinition[];
}
