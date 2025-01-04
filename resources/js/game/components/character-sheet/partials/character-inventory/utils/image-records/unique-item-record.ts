import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const uniqueItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.UNIQUE_ITEMS}/body-armour-unique.gif`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.UNIQUE_ITEMS}/boots-unique.gif`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.UNIQUE_ITEMS}/leggings-unique.gif`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.UNIQUE_ITEMS}/helmet-unique.gif`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.UNIQUE_ITEMS}/right-glove-unique.gif`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.UNIQUE_ITEMS}/left-glove-unique.gif`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.UNIQUE_ITEMS}/right-sleeve-unique.gif`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.UNIQUE_ITEMS}/left-sleeve-unique.gif`,
  },
};
