import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface ShopBuyManyProps {
  on_close: () => void;
  item: EquippableItemWithBase;
}
