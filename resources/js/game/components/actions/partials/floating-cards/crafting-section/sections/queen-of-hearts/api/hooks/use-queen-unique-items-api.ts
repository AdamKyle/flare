import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import QueenInventorySlotDefinition from '../definitions/queen-inventory-slot-definition';
import { QueenOfHeartsApiUrls } from '../enums/queen-of-hearts-api-urls';
import UseQueenUniqueItemsApiDefinition from './definitions/use-queen-unique-items-api-definition';
import UseQueenUniqueItemsApiParams from './definitions/use-queen-unique-items-api-params';

export const useQueenUniqueItemsApi = ({
  character_id,
}: UseQueenUniqueItemsApiParams): UseQueenUniqueItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
    setRefresh,
  } = UsePaginatedApiHandler<QueenInventorySlotDefinition>({
    url: QueenOfHeartsApiUrls.UNIQUE_ITEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((slot) => ({ value: slot.slot_id, label: slot.preview.name })),
    [data]
  );

  const refresh = (): void => {
    setRefresh((previousValue) => !previousValue);
  };

  return {
    items,
    loadedItems: data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
    refresh,
  };
};
