import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import SeerGemDefinition from '../definitions/seer-gem-definition';
import { SeerCampApiUrls } from '../enums/seer-camp-api-urls';
import UseSeerGemsApiDefinition from './definitions/use-seer-gems-api-definition';
import UseSeerGemsApiParams from './definitions/use-seer-gems-api-params';

export const useSeerGemsApi = ({
  character_id,
}: UseSeerGemsApiParams): UseSeerGemsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<SeerGemDefinition>({
    url: SeerCampApiUrls.GEMS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((slot) => ({
        value: slot.slot_id,
        label: `${slot.gem.name} (Tier ${slot.gem.tier}, Amount: ${slot.amount})`,
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
