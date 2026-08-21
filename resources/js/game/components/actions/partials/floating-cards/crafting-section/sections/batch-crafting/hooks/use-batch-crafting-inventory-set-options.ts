import UseBatchCraftingInventorySetOptionsDefinition from './definitions/use-batch-crafting-inventory-set-options-definition';
import UseBatchCraftingInventorySetOptionsProps from './types/use-batch-crafting-inventory-set-options-props';
import { useInventorySetOptionsApi } from '../../../../../../../side-peeks/character-inventory/sets/api/hooks/use-inventory-set-options-api';

export const useBatchCraftingInventorySetOptions = ({
  characterId,
  enabled,
}: UseBatchCraftingInventorySetOptionsProps): UseBatchCraftingInventorySetOptionsDefinition => {
  const {
    setOptions,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = useInventorySetOptionsApi({ characterId, enabled });

  return {
    setOptions,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  };
};
