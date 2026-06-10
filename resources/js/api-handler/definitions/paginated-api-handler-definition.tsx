import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { PaginatedApiResponseDefinition } from './paginated-api-response-definition';
import { StateSetter } from '../../types/state-setter-type';

export default interface PaginatedApiHandlerDefinition<
  T,
  F extends Record<string, unknown>,
  R = PaginatedApiResponseDefinition<T[]>,
> {
  data: T[];
  error: AxiosErrorDefinition | null;
  loading: boolean;
  canLoadMore: boolean;
  isLoadingMore: boolean;
  page: number;
  response: R | null;
  setPage: StateSetter<number>;
  setSearchText: StateSetter<string>;
  setFilters: StateSetter<F>;
  onEndReached: () => void;
  setRefresh: StateSetter<boolean>;
}
