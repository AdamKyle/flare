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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/dagger-one-enchant.png`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/bow-one-enchant.png`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/mac-one-enchant.png`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/sword-one-enchant.png`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/stave-one-enchant.png`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/hammer-one-enchant.png`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/scratch-awl-one-enchant.png`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/wand-one-enchant.png`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/censor-one-enchant.png`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/spell-healing-one-enchant.png`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/spell-damage-one-enchant.png`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/spell-damage-one-enchant.png`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/gun-one-enchant.png`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/fan-one-enchant.png`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/claw-one-enchant.png`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.ONE_ENCHANT_ITEMS}/shield-one-enchant.png`,
};
