import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseGemAbilitiesDefinition from './definitions/use-gem-abilities-definition';
import GemAbilityListDefinition from '../definitions/gem-ability-list-definition';
import { GemAbilityListResponseDefinition } from '../definitions/gem-ability-list-response-definition';
import { GemAbilityApiUrls } from '../enums/gem-ability-api-urls';

interface GemAbilityFilters {
  ability_type: string | null;
  [key: string]: string | null;
}

const INITIAL_FILTERS: GemAbilityFilters = { ability_type: null };

export const useGemAbilities = (): UseGemAbilitiesDefinition => {
  const [filters, setFiltersState] =
    useState<GemAbilityFilters>(INITIAL_FILTERS);
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    GemAbilityListDefinition,
    GemAbilityFilters,
    GemAbilityListResponseDefinition
  >(
    {
      url: GemAbilityApiUrls.LIST,
      initialFilters: INITIAL_FILTERS,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
  );

  const setAbilityType = (abilityType: string | null): void => {
    const nextFilters = { ...filters, ability_type: abilityType };

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

  return {
    data: paginated.data,
    loading: paginated.loading,
    error: paginated.error,
    response: paginated.response,
    search_text: paginated.searchText,
    set_search_text: paginated.setSearchText,
    page: paginated.page,
    set_page: paginated.setPage,
    ability_type: filters.ability_type,
    set_ability_type: setAbilityType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
  };
};
