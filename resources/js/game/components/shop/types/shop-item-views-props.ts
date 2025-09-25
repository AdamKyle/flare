import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface ShopItemViewProps {
  item: EquippableItemWithBase;
  close_view: () => void;
}
