import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseSkillsDefinition from './definitions/use-skills-definition';
import SkillListDefinition from '../definitions/skill-list-definition';
import { SkillListResponseDefinition } from '../definitions/skill-list-response-definition';
import { SkillApiUrls } from '../enums/skill-api-urls';

export const useSkills = (): UseSkillsDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    SkillListDefinition,
    Record<string, never>,
    SkillListResponseDefinition
  >(
    {
      url: SkillApiUrls.LIST,
      additionalParams: {
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    15
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
