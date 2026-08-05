import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import EnchantingPaginatedItemDefinition from '../definitions/enchanting-paginated-item-definition';
import { EnchantingApiUrls } from '../enums/enchanting-api-urls';
import UseEnchantingItemsApiDefinition from './definitions/use-enchanting-items-api-definition';
import UseEnchantingItemsApiParams from './definitions/use-enchanting-items-api-params';

export const useEnchantingItemsApi = ({
  character_id,
  source,
}: UseEnchantingItemsApiParams): UseEnchantingItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<EnchantingPaginatedItemDefinition>({
    url: EnchantingApiUrls.ITEMS,
    urlParams: { character: character_id },
    additionalParams: { source: source ?? 'regular' },
    enabled: character_id > 0 && source !== null,
  });

  const items = useMemo(
    () => data.map((item) => ({ value: item.slot_id, label: item.name })),
    [data]
  );

  return {
    items,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  };
};
