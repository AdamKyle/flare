import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

export default interface AdminPaginationControlsProps<T> {
  response: PaginatedApiResponseDefinition<T[]> | null;
  label: string;
  on_page_change: (page: number) => void;
}
