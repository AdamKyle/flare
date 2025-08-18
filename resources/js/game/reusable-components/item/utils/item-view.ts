import { formatNumberWithCommas } from './item-comparison';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

/**
 * Lowercases a string while safely handling null and undefined.
 *
 * @param {string | null | undefined} value
 * @returns {string}
 * @example
 * normalize('Wand') // 'wand'
 * normalize(null) // ''
 */
export const normalize = (value?: string | null): string => {
  if (!value) {
    return '';
  }

  return value.toLowerCase();
};

const armourTypeSet = new Set(
  [...armourPositions, InventoryItemTypes.SHIELD].map((t) =>
    String(t).toLowerCase()
  )
);

const ringTypeSet = new Set(
  [
    InventoryItemTypes.RING,
    InventoryItemTypes.RING_ONE,
    InventoryItemTypes.RING_TWO,
  ].map((t) => String(t).toLowerCase())
);

const spellTypeSet = new Set(
  [
    InventoryItemTypes.SPELL_HEALING,
    InventoryItemTypes.SPELL_DAMAGE,
    InventoryItemTypes.SPELL_ONE,
    InventoryItemTypes.SPELL_TWO,
  ].map((t) => String(t).toLowerCase())
);

/**
 * Checks if an item type should be treated as armour.
 *
 * @param {string | null} type
 * @returns {boolean}
 * @example
 * isArmourType('body') // true
 * isArmourType('wand') // false
 */
export const isArmourType = (type: string | null): boolean => {
  const normalized = normalize(type);

  if (!normalized) {
    return false;
  }

  return armourTypeSet.has(normalized);
};

/**
 * Checks if an item type is a healing spell.
 *
 * @param {string | null} type
 * @returns {boolean}
 * @example
 * isHealingSpellType('spell-healing') // true
 * isHealingSpellType('spell-damage') // false
 */
export const isHealingSpellType = (type: string | null): boolean => {
  const normalized = normalize(type);

  if (!normalized) {
    return false;
  }

  return normalized === String(InventoryItemTypes.SPELL_HEALING).toLowerCase();
};

/**
 * Returns the crafting category label for a given item type.
 *
 * @param {string | null} type
 * @returns {string}
 * @example
 * getCraftingLabelForType('wand') // 'Weapon Crafting'
 * getCraftingLabelForType('ring') // 'Ring Crafting'
 * getCraftingLabelForType('body') // 'Armour Crafting'
 * getCraftingLabelForType('spell-damage') // 'Spell Crafting'
 */
export const getCraftingLabelForType = (type: string | null): string => {
  const normalized = normalize(type);

  if (armourTypeSet.has(normalized)) {
    return 'Armour Crafting';
  }

  if (ringTypeSet.has(normalized)) {
    return 'Ring Crafting';
  }

  if (spellTypeSet.has(normalized)) {
    return 'Spell Crafting';
  }

  return 'Weapon Crafting';
};

/**
 * Resolves the primary stat label for display: AC, Healing, or Damage.
 *
 * @param {string | null} type
 * @returns {'AC' | 'Healing' | 'Damage'}
 * @example
 * getPrimaryLabelForType('body') // 'AC'
 * getPrimaryLabelForType('spell-healing') // 'Healing'
 * getPrimaryLabelForType('wand') // 'Damage'
 */
export const getPrimaryLabelForType = (
  type: string | null
): 'AC' | 'Healing' | 'Damage' => {
  if (isArmourType(type)) {
    return 'AC';
  }

  if (isHealingSpellType(type)) {
    return 'Healing';
  }

  return 'Damage';
};

/**
 * Computes the primary stat numeric value for an item.
 * Falls back to 0 when the expected raw value is missing.
 *
 * @param {{ type: string | null; raw_damage: number | null; raw_ac: number | null; raw_healing: number | null }} item
 * @returns {number}
 * @example
 * getPrimaryValueForItem({ type: 'body', raw_ac: 10, raw_damage: null, raw_healing: null }) // 10
 * getPrimaryValueForItem({ type: 'spell-healing', raw_healing: 25, raw_damage: null, raw_ac: null }) // 25
 * getPrimaryValueForItem({ type: 'wand', raw_damage: 40, raw_ac: null, raw_healing: null }) // 40
 */
export const getPrimaryValueForItem = (item: {
  type: string | null;
  raw_damage: number | null;
  raw_ac: number | null;
  raw_healing: number | null;
}): number => {
  if (isArmourType(item.type)) {
    if (typeof item.raw_ac === 'number') {
      return item.raw_ac;
    }

    return 0;
  }

  if (isHealingSpellType(item.type)) {
    if (typeof item.raw_healing === 'number') {
      return item.raw_healing;
    }

    return 0;
  }

  if (typeof item.raw_damage === 'number') {
    return item.raw_damage;
  }

  return 0;
};

/**
 * Formats an integer-like value with a leading plus.
 *
 * @param {number} value
 * @returns {string}
 * @example
 * formatSignedInt(1234) // '+1,234'
 * formatSignedInt(0) // '+0'
 */
export const formatSignedInt = (value: number): string => {
  const whole = Math.floor(value);

  return `+${formatNumberWithCommas(whole)}`;
};

/**
 * Formats a decimal ratio as a percentage with a leading plus.
 *
 * @param {number} value
 * @returns {string}
 * @example
 * formatSignedPercent(0.0015) // '+0.15%'
 * formatSignedPercent(0) // '+0.00%'
 */
export const formatSignedPercent = (value: number): string => {
  const numeric = Number(value);

  return `+${(numeric * 100).toFixed(2)}%`;
};

/**
 * Returns true when value is a positive number.
 *
 * @param {number | null | undefined} value
 * @returns {boolean}
 * @example
 * isPositiveNumber(0.1) // true
 * isPositiveNumber(0) // false
 * isPositiveNumber(null) // false
 */
export const isPositiveNumber = (value: number | null | undefined): boolean => {
  if (value == null) {
    return false;
  }

  return Number(value) > 0;
};

/**
 * Checks if an item is a ring by type.
 *
 * @param {string | null} type
 * @returns {boolean}
 * @example
 * isRingType('ring') // true
 * isRingType('ring-one') // false
 */
export const isRingType = (type: string | null): boolean => {
  const t = normalize(type);

  if (!t) {
    return false;
  }

  if (t === 'ring') {
    return true;
  }

  return false;
};

/**
 * Extracts ring resistance values, converting null/undefined to 0.
 *
 * @param {{ spell_evasion?: number | null; affix_damage_reduction?: number | null; healing_reduction?: number | null }} item
 * @returns {{ spellEvasion: number; affixDamageReduction: number; healingReduction: number }}
 * @example
 * getRingResistances({ spell_evasion: 0.02, affix_damage_reduction: null, healing_reduction: 0.01 })
 * // { spellEvasion: 0.02, affixDamageReduction: 0, healingReduction: 0.01 }
 */
export const getRingResistances = (item: {
  spell_evasion?: number | null;
  affix_damage_reduction?: number | null;
  healing_reduction?: number | null;
}): {
  spellEvasion: number;
  affixDamageReduction: number;
  healingReduction: number;
} => {
  const spellEvasion = Number(item.spell_evasion ?? 0);
  const affixDamageReduction = Number(item.affix_damage_reduction ?? 0);
  const healingReduction = Number(item.healing_reduction ?? 0);

  return {
    spellEvasion,
    affixDamageReduction,
    healingReduction,
  };
};

/**
 * Gets the resurrection chance for healing spells, null-safe.
 *
 * @param {{ resurrection_chance?: number | null }} item
 * @returns {number}
 * @example
 * getResurrectionChance({ resurrection_chance: 0.05 }) // 0.05
 * getResurrectionChance({}) // 0
 */
export const getResurrectionChance = (item: {
  resurrection_chance?: number | null;
}): number => {
  return Number(item.resurrection_chance ?? 0);
};
