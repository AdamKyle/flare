import { ItemSelectedType } from '../../types/item-selected-type';

import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-count-definition';

export default interface UseManageMultipleSelectedItemsApiParams {
  character_id: number;
  apiParams: ItemSelectedType;
  url: string;
  onSuccess: (inventory_count: InventoryCountDefinition) => void;
}
