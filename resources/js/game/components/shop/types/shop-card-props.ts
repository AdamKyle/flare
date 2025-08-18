import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface ShopCardProps {
  item: EquippableItemWithBase;
  view_item: (item_id: number) => void;
  compare_item: (item_id: number) => void;
}
