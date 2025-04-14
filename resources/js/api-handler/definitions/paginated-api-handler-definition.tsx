import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { StateSetter } from '../../types/state-setter-type';

export default interface PaginatedApiHandlerDefinition<
  T,
  F extends Record<string, unknown>,
> {
  data: T[];
  error: AxiosErrorDefinition | null;
  loading: boolean;
  canLoadMore: boolean;
  isLoadingMore: boolean;
  page: number;
  setPage: StateSetter<number>;
  setSearchText: StateSetter<string>;
  setFilters: StateSetter<F>;
  onEndReached: () => void;
}
