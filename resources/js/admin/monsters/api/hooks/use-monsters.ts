import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseMonstersDefinition from './definitions/use-monsters-definition';
import MonsterListDefinition from '../definitions/monster-list-definition';
import { MonsterListResponseDefinition } from '../definitions/monster-list-response-definition';
import { MonsterApiUrls } from '../enums/monster-api-urls';

export const useMonsters = (): UseMonstersDefinition => {
  const [gameMapId, setGameMapIdState] = useState<number | null>(null);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    MonsterListDefinition,
    Record<string, unknown>,
    MonsterListResponseDefinition
  >(
    {
      url: MonsterApiUrls.LIST,
      additionalParams: {
        filters: { game_map_id: gameMapId },
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
  );

  const setGameMapId = (nextGameMapId: number | null): void => {
    setGameMapIdState(nextGameMapId);
    paginated.setPage(1);
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
    game_map_id: gameMapId,
    set_game_map_id: setGameMapId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
