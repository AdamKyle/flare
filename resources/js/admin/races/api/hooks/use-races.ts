import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseRacesDefinition from './definitions/use-races-definition';
import RaceDefinition from '../definitions/race-definition';
import { RaceListResponseDefinition } from '../definitions/race-list-response-definition';
import { RaceApiUrls } from '../enums/race-api-urls';

export const useRaces = (): UseRacesDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const paginated = UsePaginatedApiHandler<
    RaceDefinition,
    Record<string, never>,
    RaceListResponseDefinition
  >(
    {
      url: RaceApiUrls.LIST,
      additionalParams: { sort_key: sortKey, sort_direction: sortDirection },
      paginationMode: 'replace',
    },
    15
  );

  const setSort = (nextSortKey: string): void => {
    if (nextSortKey === sortKey) {
      setSortDirection((previous) => (previous === 'asc' ? 'desc' : 'asc'));
      return;
    }
    setSortKey(nextSortKey);
    setSortDirection('asc');
  };

  const refreshFirstPage = (): void => {
    if (paginated.page !== 1) {
      paginated.setPage(1);

      return;
    }

    paginated.setRefresh((previous) => !previous);
  };

  return {
    data: paginated.data,
    loading: paginated.loading,
    error: paginated.error,
    response: paginated.response,
    search_text: paginated.searchText,
    set_search_text: paginated.setSearchText,
    page: paginated.page,
    set_page: paginated.setPage,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
