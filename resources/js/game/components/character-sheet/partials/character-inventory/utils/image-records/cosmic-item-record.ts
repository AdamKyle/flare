import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const cosmicItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.COSMIC_ITEMS}/body-armour-cosmic.gif`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.COSMIC_ITEMS}/boots-cosmic.gif`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.COSMIC_ITEMS}/leggings-cosmic.gif`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.COSMIC_ITEMS}/helmet-cosmic.gif`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.COSMIC_ITEMS}/right-glove-cosmic.gif`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.COSMIC_ITEMS}/left-glove-cosmic.gif`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.COSMIC_ITEMS}/right-sleeve-cosmic.gif`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.COSMIC_ITEMS}/left-sleeve-cosmic.gif`,
  },
};
