import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseLocationsDefinition from './definitions/use-locations-definition';
import LocationListDefinition from '../definitions/location-list-definition';
import LocationListFiltersDefinition from '../definitions/location-list-filters-definition';
import { LocationListResponseDefinition } from '../definitions/location-list-response-definition';
import { LocationApiUrls } from '../enums/location-api-urls';

const PER_PAGE = 15;

const INITIAL_FILTERS: LocationListFiltersDefinition = {
  game_map_id: null,
};

export const useLocations = (): UseLocationsDefinition => {
  const [filters, setFiltersState] =
    useState<LocationListFiltersDefinition>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    LocationListDefinition,
    LocationListFiltersDefinition,
    LocationListResponseDefinition
  >(
    {
      url: LocationApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
      enabled: filters.game_map_id !== null,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    PER_PAGE
  );

  const setGameMapId = (gameMapId: number | null): void => {
    const nextFilters: LocationListFiltersDefinition = {
      game_map_id: gameMapId,
    };

    setFiltersState(nextFilters);
    paginated.setFilters(nextFilters);
  };

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
    game_map_id: filters.game_map_id,
    set_game_map_id: setGameMapId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
