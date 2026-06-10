import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { useEffect } from 'react';

import CraftableItemDefinition from '../definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../definitions/crafting-api-response-definition';
import CraftingFiltersDefinition from '../definitions/crafting-filters-definition';
import { CraftingApiUrls } from '../enums/crafting-api-urls';
import UseCraftableItemsApiDefinition from './definitions/use-craftable-items-api-definition';
import UseCraftableItemsApiParams from './definitions/use-craftable-items-api-params';

const ITEMS_PER_PAGE = 10;

export const useCraftableItemsApi = ({
  characterId,
  selectedType,
  armourType,
}: UseCraftableItemsApiParams): UseCraftableItemsApiDefinition => {
  const canFetch = Boolean(
    characterId > 0 && selectedType && (selectedType !== 'armour' || armourType)
  );

  const url =
    selectedType === 'for-class'
      ? CraftingApiUrls.FETCH_FOR_CLASS
      : CraftingApiUrls.FETCH_ITEMS;

  const additionalParams: Record<string, unknown> | undefined =
    selectedType && selectedType !== 'for-class'
      ? { crafting_type: selectedType }
      : undefined;

  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    response,
    onEndReached,
    setSearchText,
    setFilters,
  } = UsePaginatedApiHandler<
    CraftableItemDefinition,
    CraftingFiltersDefinition,
    CraftingApiResponseDefinition
  >(
    {
      url,
      urlParams: { character: characterId },
      enabled: canFetch,
      additionalParams,
    },
    ITEMS_PER_PAGE
  );

  useEffect(() => {
    const nextFilters: CraftingFiltersDefinition =
      selectedType === 'armour' && armourType
        ? { armour_type: armourType }
        : {};
    setFilters(nextFilters);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedType, armourType]);

  return {
    items: data,
    craftingData: response,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  };
};
