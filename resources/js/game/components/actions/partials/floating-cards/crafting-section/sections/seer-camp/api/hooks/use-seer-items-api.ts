import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import SeerItemDefinition from '../definitions/seer-item-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseSeerItemsApiDefinition from './definitions/use-seer-items-api-definition';
import UseSeerItemsApiParams from './definitions/use-seer-items-api-params';

export const useSeerItemsApi = ({
  character_id,
  purpose,
}: UseSeerItemsApiParams): UseSeerItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<SeerItemDefinition>({
    url: SeerCampApiUrls.ITEMS,
    urlParams: { character: character_id },
    additionalParams: { purpose },
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
