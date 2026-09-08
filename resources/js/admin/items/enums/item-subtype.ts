import { ItemCatalogType } from './item-catalog-type';
import { ItemProfile } from './item-profile';

/**
 * Keep this list aligned with `ItemType::validWeapons()`.
 */
export const WEAPON_SUBTYPES: ItemCatalogType[] = [
  ItemCatalogType.STAVE,
  ItemCatalogType.BOW,
  ItemCatalogType.DAGGER,
  ItemCatalogType.SCRATCH_AWL,
  ItemCatalogType.MACE,
  ItemCatalogType.HAMMER,
  ItemCatalogType.GUN,
  ItemCatalogType.FAN,
  ItemCatalogType.WAND,
  ItemCatalogType.CENSER,
  ItemCatalogType.CLAW,
  ItemCatalogType.SWORD,
];

export const ARMOUR_SUBTYPES: ItemCatalogType[] = [
  ItemCatalogType.SHIELD,
  ItemCatalogType.BODY,
  ItemCatalogType.LEGGINGS,
  ItemCatalogType.SLEEVES,
  ItemCatalogType.GLOVES,
  ItemCatalogType.FEET,
  ItemCatalogType.HELMET,
];

export const subtypesForProfile = (
  profile: ItemProfile
): ItemCatalogType[] | null => {
  if (profile === ItemProfile.WEAPONS) {
    return WEAPON_SUBTYPES;
  }

  if (profile === ItemProfile.ARMOUR) {
    return ARMOUR_SUBTYPES;
  }

  return null;
};
