import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseUnitsDefinition from './definitions/use-units-definition';
import UnitListDefinition from '../definitions/unit-list-definition';
import { UnitListResponseDefinition } from '../definitions/unit-list-response-definition';
import { UnitApiUrls } from '../enums/unit-api-urls';

export const useUnits = (): UseUnitsDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    UnitListDefinition,
    Record<string, never>,
    UnitListResponseDefinition
  >(
    {
      url: UnitApiUrls.LIST,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
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
