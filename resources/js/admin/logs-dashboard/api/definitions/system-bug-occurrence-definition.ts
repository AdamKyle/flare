export default interface SystemBugOccurrenceDefinition {
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
}
