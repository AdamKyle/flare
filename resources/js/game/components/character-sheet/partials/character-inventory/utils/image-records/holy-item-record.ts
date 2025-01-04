import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const holyItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.HOLY_ITEMS}/body-armour-holy.png`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.HOLY_ITEMS}/boots-holy.png`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.HOLY_ITEMS}/leggings-holy.png`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.HOLY_ITEMS}/helmet-holy.png`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.HOLY_ITEMS}/right-glove-holy.png`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.HOLY_ITEMS}/left-glove-holy.png`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.HOLY_ITEMS}/right-sleeve-holy.png`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.HOLY_ITEMS}/left-sleeve-holy.png`,
  },
};
