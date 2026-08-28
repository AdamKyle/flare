import BugChartPointDefinition from '../../api/definitions/bug-chart-point-definition';
import {
  LogEntriesPage,
  LogFilters,
  SystemBugReport,
} from '../../types/logs-dashboard';

export default interface UseLogDashboardDataParams {
  selected_file: string;
  filters: LogFilters;
  page: number;
  bug_range: number;
  fetch_log_entries: (
    fileKey: string,
    filters: LogFilters,
    page: number
  ) => Promise<LogEntriesPage>;
  fetch_system_bugs: () => Promise<SystemBugReport[]>;
  fetch_bug_chart: (days: number) => Promise<BugChartPointDefinition[]>;
}
