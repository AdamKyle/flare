import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { formatNumberWithCommas } from 'game-utils/format-number';
import { useMemo } from 'react';

import EnchantingAffixDefinition from '../definitions/enchanting-affix-definition';
import { EnchantingApiUrls } from '../enums/enchanting-api-urls';
import UseEnchantingAffixesApiDefinition from './definitions/use-enchanting-affixes-api-definition';
import UseEnchantingAffixesApiParams from './definitions/use-enchanting-affixes-api-params';

export const useEnchantingAffixesApi = ({
  character_id,
  type,
}: UseEnchantingAffixesApiParams): UseEnchantingAffixesApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<EnchantingAffixDefinition>({
    url: EnchantingApiUrls.AFFIXES,
    urlParams: { character: character_id },
    additionalParams: { type },
    enabled: character_id > 0,
  });

  const affixes = useMemo(
    () =>
      data.map((affix) => ({
        value: affix.id,
        label: `${affix.name} [Cost: ${formatNumberWithCommas(affix.cost)}, INT REQ: ${affix.int_required}]`,
      })),
    [data]
  );

  return {
    affixes,
    loadedAffixes: data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  };
};
