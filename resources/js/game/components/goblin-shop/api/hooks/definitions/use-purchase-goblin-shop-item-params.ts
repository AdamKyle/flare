import GoblinShopPurchaseResponseDefinition from '../../definitions/goblin-shop-purchase-response-definition';

export default interface UsePurchaseGoblinShopItemParams {
  character_id: number;
  item_id: number;
  on_success: (result: GoblinShopPurchaseResponseDefinition) => void;
}
