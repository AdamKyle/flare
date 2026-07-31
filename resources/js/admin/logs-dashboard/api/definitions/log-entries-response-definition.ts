import LogEntryDefinition from './log-entry-definition';
import LogSummaryDefinition from './log-summary-definition';

export default interface LogEntriesResponseDefinition {
  data: LogEntryDefinition[];
  current_page: number;
  last_page: number;
  total: number;
  next_cursor: string | null;
  summary: LogSummaryDefinition;
}
