import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const oneEnchantItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/body-armour-one-enchant.png`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/boots-one-enchant.png`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/leggings-one-enchant.png`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/helmet-one-enchant.png`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/right-glove-one-enchant.png`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/left-glove-one-enchant.png`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/right-sleeve-one-enchant.png`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/left-sleeve-one-enchant.png`,
  },
};
