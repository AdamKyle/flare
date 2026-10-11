import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';
import {
  InventoryPositionDefinition,
  Position,
} from '../enums/equipment-positions';
import { InventoryItemTypes } from '../enums/inventory-item-types';

export default interface EquippedSlotProps {
  positionName: string;
  position: Position;
  empty_slot_item_types: InventoryItemTypes[];
  equip_position: InventoryPositionDefinition;
  equipped_item?: BaseInventoryItemDefinition;
  on_open_item_details: (equippedItem: BaseInventoryItemDefinition) => void;
  on_open_empty_slot: (
    position: InventoryPositionDefinition,
    itemTypes: InventoryItemTypes[]
  ) => void;
}
