import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import WorkBenchInventorySlotDefinition from '../definitions/work-bench-inventory-slot-definition';
import { WorkBenchApiUrls } from '../enums/work-bench-api-urls';
import UseWorkBenchItemsApiDefinition from './definitions/use-work-bench-items-api-definition';
import UseWorkBenchItemsApiParams from './definitions/use-work-bench-items-api-params';

export const useWorkBenchItemsApi = ({
  character_id,
}: UseWorkBenchItemsApiParams): UseWorkBenchItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<WorkBenchInventorySlotDefinition>({
    url: WorkBenchApiUrls.ITEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () => data.map((slot) => ({ value: slot.slot_id, label: slot.name })),
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
