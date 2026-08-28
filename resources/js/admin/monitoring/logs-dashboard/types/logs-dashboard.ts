import LogEntriesResponseDefinition from '../api/definitions/log-entries-response-definition';
import LogEntryDefinition from '../api/definitions/log-entry-definition';
import LogEntryDetailDefinition from '../api/definitions/log-entry-detail-definition';
import LogFileInfoDefinition from '../api/definitions/log-file-info-definition';
import LogFiltersDefinition from '../api/definitions/log-filters-definition';
import LogSummaryDefinition from '../api/definitions/log-summary-definition';
import LogsPollResponseDefinition from '../api/definitions/logs-poll-response-definition';
import SystemBugOccurrenceDefinition from '../api/definitions/system-bug-occurrence-definition';
import SystemBugReportDefinition from '../api/definitions/system-bug-report-definition';

export type LogFileInfo = LogFileInfoDefinition;
export type LogEntry = LogEntryDefinition;
export type LogEntryDetail = LogEntryDetailDefinition;
export type LogSummary = LogSummaryDefinition;

export type LogEntriesPage = LogEntriesResponseDefinition;

export type LogFilters = LogFiltersDefinition;

export type SystemBugOccurrence = SystemBugOccurrenceDefinition;

export type SystemBugReport = SystemBugReportDefinition;

export type LogsPollResponse = LogsPollResponseDefinition;

export const SEVERITIES = [
  '',
  'emergency',
  'alert',
  'critical',
  'error',
  'fatal',
  'warning',
  'notice',
  'info',
  'debug',
] as const;
