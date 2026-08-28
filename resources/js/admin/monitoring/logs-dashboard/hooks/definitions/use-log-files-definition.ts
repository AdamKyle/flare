import LogFileInfoDefinition from '../../api/definitions/log-file-info-definition';

export default interface UseLogFilesDefinition {
  loading: boolean;
  error: string;
  files: LogFileInfoDefinition[];
  selected_file: string;
  set_selected_file: (file: string) => void;
}
