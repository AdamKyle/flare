import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

export default interface ShopCardProps {
  item: EquippableItemWithBase;
  row_key: string;
  view_item: (item: EquippableItemWithBase) => void;
  compare_item: (item: EquippableItemWithBase) => void;
  view_buy_many: (item: EquippableItemWithBase) => void;
  on_purchase_item: (item_id: number) => void;
  is_actions_disabled?: boolean;
}
