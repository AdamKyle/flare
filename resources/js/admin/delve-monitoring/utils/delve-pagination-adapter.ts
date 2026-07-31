import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import DelvePaginationResponseDefinition from '../api/definitions/delve-pagination-response-definition';

export default function delvePaginationAdapter<T>(
  response: DelvePaginationResponseDefinition<T>
): PaginatedApiResponseDefinition<T[]> {
  return {
    data: response.data,
    meta: {
      can_load_more: response.current_page < response.last_page,
      pagination: {
        count: response.data.length,
        current_page: response.current_page,
        links: {},
        per_page: response.data.length,
        total: response.total,
        total_pages: response.last_page,
      },
    },
  };
}
