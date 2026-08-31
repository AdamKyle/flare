import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseMonstersDefinition from './definitions/use-monsters-definition';
import {
  MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE,
  MonsterListCategory,
} from '../../enums/monster-list-category';
import MonsterListDefinition from '../definitions/monster-list-definition';
import MonsterListFiltersDefinition from '../definitions/monster-list-filters-definition';
import { MonsterListResponseDefinition } from '../definitions/monster-list-response-definition';
import { MonsterApiUrls } from '../enums/monster-api-urls';

const INITIAL_FILTERS: MonsterListFiltersDefinition = {
  game_map_id: null,
  category: MonsterListCategory.ALL,
  location_type: null,
};

export const useMonsters = (): UseMonstersDefinition => {
  const [filters, setFiltersState] =
    useState<MonsterListFiltersDefinition>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    MonsterListDefinition,
    MonsterListFiltersDefinition,
    MonsterListResponseDefinition
  >(
    {
      url: MonsterApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
  );

  const applyFilters = (nextFilters: MonsterListFiltersDefinition): void => {
    setFiltersState(nextFilters);
    paginated.setFilters(nextFilters);
  };

  const setGameMapId = (gameMapId: number | null): void => {
    applyFilters({ ...filters, game_map_id: gameMapId });
  };

  const setCategory = (category: MonsterListCategory): void => {
    const categoryAllowsLocationType =
      MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE.includes(category);

    applyFilters({
      ...filters,
      category,
      location_type: categoryAllowsLocationType ? filters.location_type : null,
    });
  };

  const setLocationType = (locationType: number | null): void => {
    applyFilters({ ...filters, location_type: locationType });
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
    category: filters.category,
    set_category: setCategory,
    location_type: filters.location_type,
    set_location_type: setLocationType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
