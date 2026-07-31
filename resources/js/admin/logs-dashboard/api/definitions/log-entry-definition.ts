export default interface LogEntryDefinition {
  detail_id: string;
  file_key: string;
  timestamp: string | null;
  channel: string | null;
  severity: string;
  message: string;
  context: string | null;
  exception_class: string | null;
  exception_file: string | null;
  exception_line: number | null;
}
