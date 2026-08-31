import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useState } from 'react';

import UseItemsDefinition from './definitions/use-items-definition';
import {
  ITEM_PROFILE_DEFAULT_SORT_KEY,
  ItemProfile,
} from '../../enums/item-profile';
import ItemDefinition from '../definitions/item-definition';
import { ItemListResponseDefinition } from '../definitions/item-list-response-definition';
import { ItemApiUrls } from '../enums/item-api-urls';
import { ItemPagination } from '../enums/item-pagination';

export const useItems = (): UseItemsDefinition => {
  const [profile, setProfileState] = useState<ItemProfile>(ItemProfile.ALL);
  const [subtype, setSubtypeState] = useState<string | null>(null);
  const [sortKey, setSortKey] = useState(
    ITEM_PROFILE_DEFAULT_SORT_KEY[ItemProfile.ALL]
  );
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const paginated = UsePaginatedApiHandler<
    ItemDefinition,
    Record<string, unknown>,
    ItemListResponseDefinition
  >(
    {
      url: ItemApiUrls.LIST,
      additionalParams: {
        profile,
        subtype,
        sort_key: sortKey,
        sort_direction: sortDirection,
      },
      paginationMode: 'replace',
    },
    ItemPagination.PerPage
  );

  const setProfile = (nextProfile: ItemProfile): void => {
    setProfileState(nextProfile);
    setSubtypeState(null);
    setSortKey(ITEM_PROFILE_DEFAULT_SORT_KEY[nextProfile]);
    setSortDirection('asc');
    paginated.setPage(1);
  };

  const setSubtype = (nextSubtype: string | null): void => {
    setSubtypeState(nextSubtype);
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
    profile,
    set_profile: setProfile,
    subtype,
    set_subtype: setSubtype,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  };
};
