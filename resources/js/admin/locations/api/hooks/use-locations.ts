import { useState } from 'react';

import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseLocationsDefinition from './definitions/use-locations-definition';
import LocationListDefinition from '../definitions/location-list-definition';
import { LocationListResponseDefinition } from '../definitions/location-list-response-definition';
import { LocationApiUrls } from '../enums/location-api-urls';

const PER_PAGE = 15;

export const useLocations = (): UseLocationsDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    LocationListDefinition,
    Record<string, unknown>,
    LocationListResponseDefinition
  >(
    {
      url: LocationApiUrls.LIST,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    PER_PAGE
  );

  const setSort = (sortKeyToApply: string): void => {
    if (sortKeyToApply === sortKey) {
      setSortDirection((previous) => (previous === 'asc' ? 'desc' : 'asc'));

      return;
    }

    setSortKey(sortKeyToApply);
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
