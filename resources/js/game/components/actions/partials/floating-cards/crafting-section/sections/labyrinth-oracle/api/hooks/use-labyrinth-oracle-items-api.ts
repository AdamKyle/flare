import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import LabyrinthInventoryItemDefinition from '../definitions/labyrinth-inventory-item-definition';
import { LabyrinthOracleApiUrls } from '../enums/labyrinth-oracle-api-urls';
import UseLabyrinthOracleItemsApiDefinition from './definitions/use-labyrinth-oracle-items-api-definition';
import UseLabyrinthOracleItemsApiParams from './definitions/use-labyrinth-oracle-items-api-params';

export const useLabyrinthOracleItemsApi = ({
  character_id,
}: UseLabyrinthOracleItemsApiParams): UseLabyrinthOracleItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<LabyrinthInventoryItemDefinition>({
    url: LabyrinthOracleApiUrls.ITEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () => data.map((item) => ({ value: item.id, label: item.preview.name })),
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
