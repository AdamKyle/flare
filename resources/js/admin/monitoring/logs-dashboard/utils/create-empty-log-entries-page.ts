import LogEntriesResponseDefinition from '../api/definitions/log-entries-response-definition';

export default function createEmptyLogEntriesPage(): LogEntriesResponseDefinition {
  return {
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
    next_cursor: null,
    summary: { total: 0, by_severity: {}, chart: [] },
  };
}
