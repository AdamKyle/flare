import LogEntryDefinition from './log-entry-definition';
import LogFileInfoDefinition from './log-file-info-definition';
import LogSummaryDefinition from './log-summary-definition';
import SystemBugReportDefinition from './system-bug-report-definition';

export default interface LogsPollResponseDefinition {
  entries: LogEntryDefinition[];
  summary: LogSummaryDefinition;
  files: LogFileInfoDefinition[];
  bugs: SystemBugReportDefinition[];
  bug_chart: Array<{ period: string; occurrences: number }>;
}
