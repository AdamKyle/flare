import {
  armourPositions,
  InventoryItemTypes,
  weaponTypes,
} from '../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { BackpackEquipmentFilter } from '../enums/backpack-equipment-filter';
import { BackpackRarityFilter } from '../enums/backpack-rarity-filter';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const equipmentOptions: DropdownItem[] = [
  { label: 'Weapon', value: BackpackEquipmentFilter.WEAPON },
  { label: 'Armour', value: BackpackEquipmentFilter.ARMOUR },
  { label: 'Spells', value: BackpackEquipmentFilter.SPELLS },
  { label: 'Trinkets', value: BackpackEquipmentFilter.TRINKETS },
  { label: 'Artifacts', value: BackpackEquipmentFilter.ARTIFACTS },
  { label: 'Rings', value: BackpackEquipmentFilter.RINGS },
];

export const weaponItemTypes = weaponTypes.filter(
  (itemType) => itemType !== InventoryItemTypes.SHIELD
);

export const weaponSubtypeOptions: DropdownItem[] = [
  { label: 'Stave', value: InventoryItemTypes.STAVE },
  { label: 'Bow', value: InventoryItemTypes.BOW },
  { label: 'Dagger', value: InventoryItemTypes.DAGGER },
  { label: 'Scratch Awl', value: InventoryItemTypes.SCRATCH_AWL },
  { label: 'Mace', value: InventoryItemTypes.MACE },
  { label: 'Hammer', value: InventoryItemTypes.HAMMER },
  { label: 'Gun', value: InventoryItemTypes.GUN },
  { label: 'Fan', value: InventoryItemTypes.FAN },
  { label: 'Wand', value: InventoryItemTypes.WAND },
  { label: 'Censer', value: InventoryItemTypes.CENSER },
  { label: 'Claw', value: InventoryItemTypes.CLAW },
  { label: 'Sword', value: InventoryItemTypes.SWORD },
];

export const armourItemTypes = [...armourPositions, InventoryItemTypes.SHIELD];

export const armourSubtypeOptions: DropdownItem[] = [
  { label: 'Body', value: InventoryItemTypes.BODY },
  { label: 'Helmet', value: InventoryItemTypes.HELMET },
  { label: 'Sleeves', value: InventoryItemTypes.SLEEVES },
  { label: 'Gloves', value: InventoryItemTypes.GLOVES },
  { label: 'Leggings', value: InventoryItemTypes.LEGGINGS },
  { label: 'Feet', value: InventoryItemTypes.FEET },
  { label: 'Shield', value: InventoryItemTypes.SHIELD },
];

export const rarityOptions: DropdownItem[] = [
  { label: 'Unique Items', value: BackpackRarityFilter.UNIQUE },
  { label: 'Mythic', value: BackpackRarityFilter.MYTHIC },
  { label: 'Cosmic', value: BackpackRarityFilter.COSMIC },
  { label: 'Single Enchanted', value: BackpackRarityFilter.SINGLE_ENCHANTED },
  { label: 'Dual Enchanted', value: BackpackRarityFilter.DUAL_ENCHANTED },
];

export const resolveItemType = (
  value: DropdownItem['value']
): InventoryItemTypes | null =>
  Object.values(InventoryItemTypes).find((itemType) => itemType === value) ??
  null;

export const resolveEquipmentItemTypes = (
  equipmentFilter: BackpackEquipmentFilter | null,
  equipmentSubtype: InventoryItemTypes | null
): InventoryItemTypes[] | undefined => {
  if (equipmentFilter === BackpackEquipmentFilter.WEAPON) {
    return equipmentSubtype === null ? weaponItemTypes : [equipmentSubtype];
  }

  if (equipmentFilter === BackpackEquipmentFilter.ARMOUR) {
    return equipmentSubtype === null ? armourItemTypes : [equipmentSubtype];
  }

  if (equipmentFilter === BackpackEquipmentFilter.SPELLS) {
    return [InventoryItemTypes.SPELL_DAMAGE, InventoryItemTypes.SPELL_HEALING];
  }

  if (equipmentFilter === BackpackEquipmentFilter.TRINKETS) {
    return [InventoryItemTypes.TRINKET];
  }

  if (equipmentFilter === BackpackEquipmentFilter.ARTIFACTS) {
    return [InventoryItemTypes.ARTIFACT];
  }

  if (equipmentFilter === BackpackEquipmentFilter.RINGS) {
    return [InventoryItemTypes.RING];
  }

  return undefined;
};

export const resolveEquipmentFilter = (
  value: DropdownItem['value']
): BackpackEquipmentFilter | null =>
  Object.values(BackpackEquipmentFilter).find((filter) => filter === value) ??
  null;

export const resolveRarityFilter = (
  value: DropdownItem['value']
): BackpackRarityFilter | null =>
  Object.values(BackpackRarityFilter).find((filter) => filter === value) ??
  null;
