import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import QueenInventorySlotDefinition from '../definitions/queen-inventory-slot-definition';
import { QueenOfHeartsApiUrls } from '../enums/queen-of-hearts-api-urls';
import UseQueenDestinationItemsApiDefinition from './definitions/use-queen-destination-items-api-definition';
import UseQueenDestinationItemsApiParams from './definitions/use-queen-destination-items-api-params';

export const useQueenDestinationItemsApi = ({
  character_id,
  source_slot_id,
}: UseQueenDestinationItemsApiParams): UseQueenDestinationItemsApiDefinition => {
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
    url: QueenOfHeartsApiUrls.DESTINATION_ITEMS,
    urlParams: { character: character_id },
    additionalParams: { source_slot_id },
    enabled: character_id > 0 && source_slot_id !== null,
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
