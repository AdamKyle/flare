import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import TrinketDefinition from '../definitions/trinket-definition';
import { TrinketryApiUrls } from '../enums/trinketry-api-urls';
import UseTrinketryItemsApiDefinition from './definitions/use-trinketry-items-api-definition';
import UseTrinketryItemsApiParams from './definitions/use-trinketry-items-api-params';

import { formatNumberWithCommas } from 'game-utils/format-number';

export const useTrinketryItemsApi = ({
  character_id,
}: UseTrinketryItemsApiParams): UseTrinketryItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<TrinketDefinition>({
    url: TrinketryApiUrls.ITEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((item) => ({
        value: item.id,
        label: `${item.preview.name} [Gold Dust: ${formatNumberWithCommas(item.gold_dust_cost)}, Copper Coins: ${formatNumberWithCommas(item.copper_coin_cost)}]`,
      })),
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
