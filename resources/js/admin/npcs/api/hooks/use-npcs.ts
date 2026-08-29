import { useState } from 'react';

import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import UseNpcsDefinition from './definitions/use-npcs-definition';
import NpcListDefinition from '../definitions/npc-list-definition';
import { NpcListResponseDefinition } from '../definitions/npc-list-response-definition';
import { NpcApiUrls } from '../enums/npc-api-urls';

const PER_PAGE = 15;

export const useNpcs = (): UseNpcsDefinition => {
  const [sortKey, setSortKey] = useState('real_name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    NpcListDefinition,
    Record<string, unknown>,
    NpcListResponseDefinition
  >(
    {
      url: NpcApiUrls.LIST,
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
