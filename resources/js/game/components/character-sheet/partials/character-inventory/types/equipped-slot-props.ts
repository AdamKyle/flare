import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';
import { Position } from '../enums/equipment-positions';

export default interface EquippedSlotProps {
  positionName: string;
  position: Position;
  equipped_item?: BaseInventoryItemDefinition;
}
