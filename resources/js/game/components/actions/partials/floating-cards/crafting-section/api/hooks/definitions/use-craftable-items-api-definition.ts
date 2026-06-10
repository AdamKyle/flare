import { StateSetter } from '../../../../../../../../../types/state-setter-type';
import CraftableItemDefinition from '../../definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftableItemsApiDefinition {
  items: CraftableItemDefinition[];
  craftingData: CraftingApiResponseDefinition | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  onEndReached: () => void;
  setSearchText: StateSetter<string>;
}
