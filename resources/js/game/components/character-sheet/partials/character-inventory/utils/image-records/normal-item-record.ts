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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.NORMAL_ITEMS}/dagger-normal.png`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.NORMAL_ITEMS}/bow-normal.png`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.NORMAL_ITEMS}/mac-normal.png`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.NORMAL_ITEMS}/sword-normal.png`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.NORMAL_ITEMS}/stave-normal.png`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.NORMAL_ITEMS}/hammer-normal.png`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.NORMAL_ITEMS}/scratch-awl-normal.png`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.NORMAL_ITEMS}/wand-normal.png`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.NORMAL_ITEMS}/censor-normal.png`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.NORMAL_ITEMS}/spell-healing-normal.png`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.NORMAL_ITEMS}/spell-damage-normal.png`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.NORMAL_ITEMS}/spell-damage-normal.png`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.NORMAL_ITEMS}/gun-normal.png`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.NORMAL_ITEMS}/fan-normal.png`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.NORMAL_ITEMS}/claw-normal.png`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.NORMAL_ITEMS}/shield-normal.png`,
};
