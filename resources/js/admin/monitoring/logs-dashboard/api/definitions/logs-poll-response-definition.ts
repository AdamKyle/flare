import LogEntryDefinition from './log-entry-definition';
import LogSummaryDefinition from './log-summary-definition';

export default interface LogsPollResponseDefinition {
  entries: LogEntryDefinition[];
  summary: LogSummaryDefinition;
}
