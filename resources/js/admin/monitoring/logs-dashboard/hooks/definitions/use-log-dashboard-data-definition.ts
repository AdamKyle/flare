import BugChartPointDefinition from '../../api/definitions/bug-chart-point-definition';
import {
  LogEntriesPage,
  LogSummary,
  SystemBugReport,
} from '../../types/logs-dashboard';

export default interface UseLogDashboardDataDefinition {
  entries: LogEntriesPage;
  summary: LogSummary;
  bugs: SystemBugReport[];
  bug_chart: BugChartPointDefinition[];
  data_error: string;
  load_data: () => Promise<void>;
}
