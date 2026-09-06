import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseMapGemsDefinition from './definitions/use-map-gems-definition';
import MapGemListDefinition from '../definitions/map-gem-list-definition';
import { MapGemListResponseDefinition } from '../definitions/map-gem-list-response-definition';
import { MapGemApiUrls } from '../enums/map-gem-api-urls';

interface MapGemFilters {
  game_map_id: number | null;
  [key: string]: number | null;
}

const INITIAL_FILTERS: MapGemFilters = { game_map_id: null };

export const useMapGems = (): UseMapGemsDefinition => {
  const [filters, setFiltersState] = useState<MapGemFilters>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    MapGemListDefinition,
    MapGemFilters,
    MapGemListResponseDefinition
  >(
    {
      url: MapGemApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
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

  const setGameMapId = (gameMapId: number | null): void => {
    const nextFilters = { ...filters, game_map_id: gameMapId };

    setFiltersState(nextFilters);
    paginated.setFilters(nextFilters);
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
    game_map_id: filters.game_map_id,
    set_game_map_id: setGameMapId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
