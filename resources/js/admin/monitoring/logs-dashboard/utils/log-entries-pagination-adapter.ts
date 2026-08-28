import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import LogEntriesResponseDefinition from '../api/definitions/log-entries-response-definition';
import LogEntryDefinition from '../api/definitions/log-entry-definition';

export default function logEntriesPaginationAdapter(
  entries: LogEntriesResponseDefinition
): PaginatedApiResponseDefinition<LogEntryDefinition[]> {
  return {
    data: entries.data,
    meta: {
      can_load_more: entries.current_page < entries.last_page,
      pagination: {
        count: entries.data.length,
        current_page: entries.current_page,
        links: {},
        per_page: entries.data.length,
        total: entries.total,
        total_pages: entries.last_page,
      },
    },
  };
}
