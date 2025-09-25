import BaseUsableItemDefinition from '../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface GoblinShopItemViewProps {
  item: BaseUsableItemDefinition;
  on_close: () => void;
}
