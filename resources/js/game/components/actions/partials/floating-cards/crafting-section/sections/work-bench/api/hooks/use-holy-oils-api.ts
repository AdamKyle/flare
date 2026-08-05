import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useMemo } from 'react';

import WorkBenchAlchemySlotDefinition from '../definitions/work-bench-alchemy-slot-definition';
import { WorkBenchApiUrls } from '../enums/work-bench-api-urls';
import UseHolyOilsApiDefinition from './definitions/use-holy-oils-api-definition';
import UseHolyOilsApiParams from './definitions/use-holy-oils-api-params';

export const useHolyOilsApi = ({
  character_id,
}: UseHolyOilsApiParams): UseHolyOilsApiDefinition => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    searchText,
    setSearchText,
    onEndReached,
  } = UsePaginatedApiHandler<WorkBenchAlchemySlotDefinition>({
    url: WorkBenchApiUrls.OILS,
    urlParams: { character: character_id },
    enabled: character_id > 0,
  });

  const items = useMemo(
    () =>
      data.map((slot) => ({
        value: slot.id,
        label: `${slot.name} (Stacks: ${slot.stack_amount})`,
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
