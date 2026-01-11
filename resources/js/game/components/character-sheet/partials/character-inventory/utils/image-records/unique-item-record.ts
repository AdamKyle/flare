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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.UNIQUE_ITEMS}/dagger-unique.gif`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.UNIQUE_ITEMS}/bow-unique.gif`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.UNIQUE_ITEMS}/mac-unique.gif`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.UNIQUE_ITEMS}/sword-unique.gif`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.UNIQUE_ITEMS}/stave-unique.gif`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.UNIQUE_ITEMS}/hammer-unique.gif`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.UNIQUE_ITEMS}/scratch-awl-unique.gif`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.UNIQUE_ITEMS}/wand-unique.gif`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.UNIQUE_ITEMS}/censor-unique.gif`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.UNIQUE_ITEMS}/spell-healing-unique.gif`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.UNIQUE_ITEMS}/spell-damage-unique.gif`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.UNIQUE_ITEMS}/spell-damage-unique.gif`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.UNIQUE_ITEMS}/gun-unique.gif`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.UNIQUE_ITEMS}/fan-unique.gif`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.UNIQUE_ITEMS}/claw-unique.gif`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.UNIQUE_ITEMS}/shield-unique.gif`,
};
