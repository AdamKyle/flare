import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import { CharacterInventoryApiUrls } from '../../../api/enums/character-inventory-api-urls';
import InventorySetOptionDefinition from '../definitions/inventory-set-option-definition';

const SET_OPTIONS_PER_PAGE = 10;

interface UseInventorySetOptionsApiParams {
  characterId: number;
  enabled: boolean;
}

export const useInventorySetOptionsApi = ({
  characterId,
  enabled,
}: UseInventorySetOptionsApiParams) => {
  const {
    data,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = UsePaginatedApiHandler<InventorySetOptionDefinition>(
    {
      url: CharacterInventoryApiUrls.CHARACTER_SET_OPTIONS,
      urlParams: { character: characterId },
      enabled: enabled && characterId > 0,
    },
    SET_OPTIONS_PER_PAGE
  );

  return {
    setOptions: data,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  };
};
