import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import InventoryCountDefinition from "game-data/api-data-definitions/character/inventory-count-definition";

export default interface GoblinShopContextDefinition {
  data: BaseUsableItemDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
  gold_bars: number;
  inventory_count: InventoryCountDefinition;
  inventoryIsFull: boolean;
}
