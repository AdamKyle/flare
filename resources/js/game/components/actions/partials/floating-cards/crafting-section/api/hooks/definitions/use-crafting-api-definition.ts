import CraftableItemDefinition from '../../definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftingApiDefinition {
  items: CraftableItemDefinition[];
  craftingData: CraftingApiResponseDefinition | null;
  loading: boolean;
  isLoadingMore: boolean;
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  fetchFirstPage: () => void;
  loadMore: () => void;
  craftItem: (
    item: CraftableItemDefinition,
    craftForNpc: boolean,
    craftForEvent: boolean
  ) => void;
  clearMessages: () => void;
}
