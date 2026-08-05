import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import SeerItemDefinition from '../definitions/seer-item-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseSeerItemsWithGemsApiDefinition from './definitions/use-seer-items-with-gems-api-definition';
import UseSeerItemsWithGemsApiParams from './definitions/use-seer-items-with-gems-api-params';

export const useSeerItemsWithGemsApi = ({
  character_id,
}: UseSeerItemsWithGemsApiParams): UseSeerItemsWithGemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<SeerItemDefinition>({
    url: SeerCampApiUrls.ITEMS_WITH_GEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((item) => ({ value: item.slot_id, label: item.preview.name })),
    [data]
  );

  return {
    items,
    loadedItems: data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  };
};
