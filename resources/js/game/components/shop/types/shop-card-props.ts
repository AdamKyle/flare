import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface ShopCardProps {
  item: EquippableItemWithBase;
  view_item: (item_id: number) => void;
  compare_item: (item_id: number) => void;
  view_buy_many: (item_id: number) => void;
  on_purchase_item: (item_id: number) => void;
  is_actions_disabled?: boolean;
}
