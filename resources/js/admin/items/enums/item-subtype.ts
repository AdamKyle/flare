import { ItemCatalogType } from './item-catalog-type';
import { ItemProfile } from './item-profile';

/**
 * Mirrors `App\Game\Core\Items\Values\ItemType::validWeapons()` exactly:
 * every weapon `items.type` value except the generic `weapon` placeholder,
 * the legacy `censor` type, spells, rings, trinkets, and artifacts.
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

/**
 * Mirrors `App\Game\Core\Items\Values\ArmourType::allTypes()` exactly.
 */
export const ARMOUR_SUBTYPES: ItemCatalogType[] = [
  ItemCatalogType.SHIELD,
  ItemCatalogType.BODY,
  ItemCatalogType.LEGGINGS,
  ItemCatalogType.SLEEVES,
  ItemCatalogType.GLOVES,
  ItemCatalogType.FEET,
  ItemCatalogType.HELMET,
];

/**
 * Return the valid subtype values for the given Item profile's secondary
 * subtype filter, or null when that profile does not support one. Only the
 * Weapons and Armour profiles support a subtype filter.
 */
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
