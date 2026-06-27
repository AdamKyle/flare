export default interface LogEntryDefinition {
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
}
