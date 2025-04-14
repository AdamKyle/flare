import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import SidePeekProps from "ui/side-peek/types/side-peek-props";

export default interface UsableItemProps extends SidePeekProps {
  item: BaseInventoryItemDefinition;
}
