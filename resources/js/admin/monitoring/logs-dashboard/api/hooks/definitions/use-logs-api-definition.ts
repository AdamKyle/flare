import {
  LogEntriesPage,
  LogFileInfo,
  LogFilters,
  LogsPollResponse,
  SystemBugReport,
} from '../../../types/logs-dashboard';
import BugChartPointDefinition from '../../definitions/bug-chart-point-definition';
import LogEntryDetailDefinition from '../../definitions/log-entry-detail-definition';

export default interface UseLogsApiDefinition {
  fetchLogFiles: () => Promise<LogFileInfo[]>;
  fetchLogEntries: (
    fileKey: string,
    filters: LogFilters,
    page: number
  ) => Promise<LogEntriesPage>;
  fetchLogEntryDetail: (
    fileKey: string,
    detailId: string
  ) => Promise<LogEntryDetailDefinition>;
  pollLogs: (fileKey: string, filters: LogFilters) => Promise<LogsPollResponse>;
  fetchSystemBugs: () => Promise<SystemBugReport[]>;
  fetchBugChart: (days: number) => Promise<BugChartPointDefinition[]>;
}
