export default interface LogFileInfoDefinition {
  key: string;
  label: string;
  exists: boolean;
  size_bytes: number;
  files: string[];
}
