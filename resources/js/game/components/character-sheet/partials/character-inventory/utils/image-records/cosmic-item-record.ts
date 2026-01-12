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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.COSMIC_ITEMS}/dagger-cosmic.gif`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.COSMIC_ITEMS}/bow-cosmic.gif`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.COSMIC_ITEMS}/mac-cosmic.gif`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.COSMIC_ITEMS}/sword-cosmic.gif`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.COSMIC_ITEMS}/stave-cosmic.gif`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.COSMIC_ITEMS}/hammer-cosmic.gif`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.COSMIC_ITEMS}/scratch-awl-cosmic.gif`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.COSMIC_ITEMS}/wand-cosmic.gif`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.COSMIC_ITEMS}/censor-cosmic.gif`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.COSMIC_ITEMS}/spell-healing-cosmic.gif`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.COSMIC_ITEMS}/spell-damage-cosmic.gif`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.COSMIC_ITEMS}/ring-cosmic.gif`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.COSMIC_ITEMS}/gun-cosmic.gif`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.COSMIC_ITEMS}/fan-cosmic.gif`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.COSMIC_ITEMS}/claw-cosmic.gif`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.COSMIC_ITEMS}/shield-cosmic.gif`,
};
