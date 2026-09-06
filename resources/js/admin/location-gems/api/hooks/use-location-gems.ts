import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseLocationGemsDefinition from './definitions/use-location-gems-definition';
import LocationGemListDefinition from '../definitions/location-gem-list-definition';
import { LocationGemListResponseDefinition } from '../definitions/location-gem-list-response-definition';
import { LocationGemApiUrls } from '../enums/location-gem-api-urls';

interface LocationGemFilters {
  game_map_id: number | null;
  location_id: number | null;
  [key: string]: number | null;
}

const INITIAL_FILTERS: LocationGemFilters = {
  game_map_id: null,
  location_id: null,
};

export const useLocationGems = (): UseLocationGemsDefinition => {
  const [filters, setFiltersState] =
    useState<LocationGemFilters>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    LocationGemListDefinition,
    LocationGemFilters,
    LocationGemListResponseDefinition
  >(
    {
      url: LocationGemApiUrls.LIST,
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
    const nextFilters = {
      ...filters,
      game_map_id: gameMapId,
      location_id: null,
    };

    setFiltersState(nextFilters);
    paginated.setFilters(nextFilters);
  };

  const setLocationId = (locationId: number | null): void => {
    const nextFilters = { ...filters, location_id: locationId };

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
    location_id: filters.location_id,
    set_location_id: setLocationId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
