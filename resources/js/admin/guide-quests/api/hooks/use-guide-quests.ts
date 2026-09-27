import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseGuideQuestsDefinition from './definitions/use-guide-quests-definition';
import GuideQuestListDefinition from '../definitions/guide-quest-list-definition';
import { GuideQuestListResponseDefinition } from '../definitions/guide-quest-list-response-definition';
import { GuideQuestApiUrls } from '../enums/guide-quest-api-urls';

export const useGuideQuests = (): UseGuideQuestsDefinition => {
  const [sortKey, setSortKey] = useState('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const paginated = UsePaginatedApiHandler<
    GuideQuestListDefinition,
    Record<string, never>,
    GuideQuestListResponseDefinition
  >(
    {
      url: GuideQuestApiUrls.LIST,
      additionalParams: { sort_key: sortKey, sort_direction: sortDirection },
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
  };
};
