import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseGameMapsDefinition from './definitions/use-game-maps-definition';
import GameMapDefinition from '../definitions/game-map-definition';
import { GameMapListResponseDefinition } from '../definitions/game-map-list-response-definition';
import { GameMapApiUrls } from '../enums/game-map-api-urls';
import { GameMapPagination } from '../enums/game-map-pagination';

export const useGameMaps = (): UseGameMapsDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    GameMapDefinition,
    Record<string, unknown>,
    GameMapListResponseDefinition
  >(
    {
      url: GameMapApiUrls.LIST,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    GameMapPagination.PerPage
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
