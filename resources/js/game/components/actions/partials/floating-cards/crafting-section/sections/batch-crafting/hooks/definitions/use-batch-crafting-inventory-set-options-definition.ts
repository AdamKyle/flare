import { StateSetter } from '../../../../../../../../../../types/state-setter-type';
import InventorySetOptionDefinition from '../../../../../../../../side-peeks/character-inventory/sets/api/definitions/inventory-set-option-definition';

export default interface UseBatchCraftingInventorySetOptionsDefinition {
  setOptions: InventorySetOptionDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  onEndReached: () => void;
  setSearchText: StateSetter<string>;
}
