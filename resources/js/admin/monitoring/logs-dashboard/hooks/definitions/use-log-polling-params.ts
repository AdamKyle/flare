import LogFiltersDefinition from '../../api/definitions/log-filters-definition';
import LogsPollResponseDefinition from '../../api/definitions/logs-poll-response-definition';

export default interface UseLogPollingParams {
  selected_file: string;
  filters: LogFiltersDefinition;
  poll_logs: (
    fileKey: string,
    filters: LogFiltersDefinition
  ) => Promise<LogsPollResponseDefinition>;
  on_poll: (payload: LogsPollResponseDefinition) => void;
  on_error: () => void;
}
