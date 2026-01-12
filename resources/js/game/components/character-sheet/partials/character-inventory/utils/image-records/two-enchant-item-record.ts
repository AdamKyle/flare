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
  [InventoryItemTypes.DAGGER]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/dagger-two-enchants.png`,
  [InventoryItemTypes.BOW]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/bow-two-enchants.png`,
  [InventoryItemTypes.MACE]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/mac-two-enchants.png`,
  [InventoryItemTypes.SWORD]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/sword-two-enchants.png`,
  [InventoryItemTypes.STAVE]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/stave-two-enchants.png`,
  [InventoryItemTypes.HAMMER]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/hammer-two-enchants.png`,
  [InventoryItemTypes.SCRATCH_AWL]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/scratch-awl-two-enchants.png`,
  [InventoryItemTypes.WAND]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/wand-two-enchants.png`,
  [InventoryItemTypes.CENSOR]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/censor-two-enchants.png`,
  [InventoryItemTypes.SPELL_HEALING]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/spell-healing-two-enchants.png`,
  [InventoryItemTypes.SPELL_DAMAGE]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/spell-damage-two-enchants.png`,
  [InventoryItemTypes.RING]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/ring-two-enchants.png`,
  [InventoryItemTypes.GUN]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/gun-two-enchants.png`,
  [InventoryItemTypes.FAN]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/fan-two-enchants.png`,
  [InventoryItemTypes.CLAW]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/claw-two-enchants.png`,
  [InventoryItemTypes.SHIELD]: `${EquipmentImagePaths.TWO_ENCHANT_ITEMS}/shield-two-enchants.png`,
};
