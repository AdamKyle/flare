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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/dagger-mythical.gif`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/bow-mythical.gif`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/mac-mythical.gif`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/sword-mythical.gif`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/stave-mythical.gif`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/hammer-mythical.gif`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/scratch-awl-mythical.gif`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/wand-mythical.gif`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/censor-mythical.gif`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/spell-healing-mythical.gif`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/spell-damage-mythical.gif`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/spell-damage-mythical.gif`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/gun-mythical.gif`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/fan-mythical.gif`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/claw-mythical.gif`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.MYTHICAL_ITEMS}/shield-mythical.gif`,
};
