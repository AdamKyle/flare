import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GoblinShopPurchaseResponseDefinition from '../../definitions/goblin-shop-purchase-response-definition';

export default interface UsePurchaseGoblinShopItemDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  purchase: (
    amount: number
  ) => Promise<GoblinShopPurchaseResponseDefinition | null>;
}
