import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import AlchemyItemDefinition from '../definitions/alchemy-item-definition';
import { AlchemyApiUrls } from '../enums/alchemy-api-urls';
import UseAlchemyItemsApiDefinition from './definitions/use-alchemy-items-api-definition';
import UseAlchemyItemsApiParams from './definitions/use-alchemy-items-api-params';

import { formatNumberWithCommas } from 'game-utils/format-number';

export const useAlchemyItemsApi = ({
  character_id,
}: UseAlchemyItemsApiParams): UseAlchemyItemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<AlchemyItemDefinition>({
    url: AlchemyApiUrls.ITEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((item) => ({
        value: item.id,
        label: `${item.name} [Gold Dust: ${formatNumberWithCommas(item.gold_dust_cost)}, Shards: ${formatNumberWithCommas(item.shards_cost)}, Owned: ${formatNumberWithCommas(item.owned_amount)}]`,
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
