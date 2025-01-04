import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const mythicalItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/body-armour-mythical.gif`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/boots-mythical.gif`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/leggings-mythical.gif`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/helmet-mythical.gif`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/right-glove-mythical.gif`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/left-glove-mythical.gif`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/right-sleeve-mythical.gif`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/left-sleeve-mythical.gif`,
  },
};
