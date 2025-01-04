import { PartialPositionRecordDefinition } from './definitions/partial-position-record-definition';
import { EquipmentImagePaths, Position } from '../../enums/equipment-positions';
import { InventoryItemTypes } from '../../enums/inventory-item-types';

/**
 * Normal item paths
 *
 * TODO: we are missing the rest of inventory types, but we dont have art for those yet. Implement when ready.
 *
 */
export const twoEnchantItemRecord: Partial<
  Record<InventoryItemTypes, string | PartialPositionRecordDefinition>
> = {
  [InventoryItemTypes.BODY]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/body-armour-two-enchants.png`,
  [InventoryItemTypes.FEET]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/boots-two-enchants.png`,
  [InventoryItemTypes.LEGGINGS]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/leggings-two-enchants.png`,
  [InventoryItemTypes.HELMET]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/helmet-two-enchants.png`,
  [InventoryItemTypes.GLOVES]: {
    [Position.GLOVES_RIGHT]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/right-glove-two-enchants.png`,
    [Position.GLOVES_LEFT]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/left-glove-two-enchants.png`,
  },
  [InventoryItemTypes.SLEEVES]: {
    [Position.SLEEVES_RIGHT]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/right-sleeve-two-enchants.png`,
    [Position.SLEEVES_LEFT]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/left-sleeve-two-enchants.png`,
  },
};
