import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseNpcsDefinition from './definitions/use-npcs-definition';
import { NpcType } from '../../enums/npc-type';
import NpcListDefinition from '../definitions/npc-list-definition';
import NpcListFiltersDefinition from '../definitions/npc-list-filters-definition';
import { NpcListResponseDefinition } from '../definitions/npc-list-response-definition';
import { NpcApiUrls } from '../enums/npc-api-urls';

const PER_PAGE = 15;

const INITIAL_FILTERS: NpcListFiltersDefinition = {
  game_map_id: null,
  type: null,
};

export const useNpcs = (): UseNpcsDefinition => {
  const [filters, setFiltersState] =
    useState<NpcListFiltersDefinition>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('real_name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    NpcListDefinition,
    NpcListFiltersDefinition,
    NpcListResponseDefinition
  >(
    {
      url: NpcApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    PER_PAGE
  );

  const applyFilters = (nextFilters: NpcListFiltersDefinition): void => {
    setFiltersState(nextFilters);
    paginated.setFilters(nextFilters);
  };

  const setGameMapId = (gameMapId: number | null): void => {
    applyFilters({ ...filters, game_map_id: gameMapId });
  };

  const setType = (type: NpcType | null): void => {
    applyFilters({ ...filters, type });
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
    type: filters.type,
    set_type: setType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
