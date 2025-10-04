import BaseUsableItemDefinition from '../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface GoblinShopCardProps {
  item: BaseUsableItemDefinition;
  view_item: (item_id: number) => void;
  action_disabled: boolean
}
