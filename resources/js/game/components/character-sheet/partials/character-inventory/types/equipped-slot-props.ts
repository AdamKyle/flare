import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import { Position } from '../enums/equipment-positions';

export default interface EquippedSlotProps {
  positionName: string;
  position: Position;
  equipped_item?: BaseInventoryItemDefinition;
}
