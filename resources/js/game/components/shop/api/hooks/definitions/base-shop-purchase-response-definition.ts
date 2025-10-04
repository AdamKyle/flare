import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-count-definition';

export interface PurchaseResponse {
  message: string;
  inventory_count: InventoryCountDefinition;
  gold: number;
}

export default interface BaseShopPurchaseResponseDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
