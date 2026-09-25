import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface GoblinShopPurchaseFormProps {
  item: BaseUsableItemDefinition;
  gold_bars: number;
  purchase_disabled: boolean;
}
