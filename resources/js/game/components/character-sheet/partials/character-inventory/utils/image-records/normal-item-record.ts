import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const normalItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.NORMAL_ITEMS}/body-armour-normal.png`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.NORMAL_ITEMS}/boots-normal.png`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.NORMAL_ITEMS}/leggings-normal.png`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.NORMAL_ITEMS}/helmet-normal.png`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.NORMAL_ITEMS}/right-glove-normal.png`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.NORMAL_ITEMS}/left-glove-normal.png`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.NORMAL_ITEMS}/right-sleeve-normal.png`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.NORMAL_ITEMS}/left-sleeve-normal.png`,
  },
};
