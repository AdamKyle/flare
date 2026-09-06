import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseClassMasteriesDefinition from './definitions/use-class-masteries-definition';
import ClassMasteryListDefinition from '../definitions/class-mastery-list-definition';
import { ClassMasteryListResponseDefinition } from '../definitions/class-mastery-list-response-definition';
import { ClassMasteryApiUrls } from '../enums/class-mastery-api-urls';

interface ClassMasteryFilters {
  game_class_id: number | null;
  [key: string]: number | null;
}

const INITIAL_FILTERS: ClassMasteryFilters = { game_class_id: null };

export const useClassMasteries = (): UseClassMasteriesDefinition => {
  const [filters, setFiltersState] =
    useState<ClassMasteryFilters>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    ClassMasteryListDefinition,
    ClassMasteryFilters,
    ClassMasteryListResponseDefinition
  >(
    {
      url: ClassMasteryApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
  );

  const setGameClassId = (gameClassId: number | null): void => {
    const nextFilters = { ...filters, game_class_id: gameClassId };

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
    game_class_id: filters.game_class_id,
    set_game_class_id: setGameClassId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
