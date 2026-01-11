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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.HOLY_ITEMS}/dagger-holy.png`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.HOLY_ITEMS}/bow-holy.png`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.HOLY_ITEMS}/mac-holy.png`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.HOLY_ITEMS}/sword-holy.png`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.HOLY_ITEMS}/stave-holy.png`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.HOLY_ITEMS}/hammer-holy.png`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.HOLY_ITEMS}/scratch-awl-holy.png`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.HOLY_ITEMS}/wand-holy.png`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.HOLY_ITEMS}/censor-holy.png`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.HOLY_ITEMS}/spell-healing-holy.png`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.HOLY_ITEMS}/spell-damage-holy.png`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.HOLY_ITEMS}/spell-damage-holy.png`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.HOLY_ITEMS}/gun-holy.png`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.HOLY_ITEMS}/fan-holy.png`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.HOLY_ITEMS}/claw-holy.png`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.HOLY_ITEMS}/shield-holy.png`,
};
