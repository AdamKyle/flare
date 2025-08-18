import type {
  FieldDef,
  NumericAdjustmentKey,
} from '../types/item-comparison-types';

/**
 * Top-level totals shown in “Core Impact”.
 *
 * @example
 * // Used by AdjustmentGroup to render totals:
 * <AdjustmentGroup adjustments={a} fields={TOP_FIELDS} />
 */
export const TOP_FIELDS: FieldDef[] = [
  { key: 'total_damage_adjustment', label: 'Damage Adjustment' },
  { key: 'total_defence_adjustment', label: 'AC Adjustment' },
  { key: 'total_healing_adjustment', label: 'Healing Adjustment' },
];

/**
 * Child rows for “Core Impact” when Advanced is ON.
 * Maps a top-level total to its underlying base modifier.
 *
 * @example
 * // If the parent total is visible, and a child here is non-zero,
 * // it renders underneath the parent with an indent.
 */
export const TOP_ADVANCED_CHILD: Partial<
  Record<NumericAdjustmentKey, { key: NumericAdjustmentKey; label: string }>
> = {
  total_damage_adjustment: {
    key: 'base_damage_mod_adjustment',
    label: 'Base Damage Mod',
  },

  total_healing_adjustment: {
    key: 'base_healing_mod_adjustment',
    label: 'Base Healing Mod',
  },

  total_defence_adjustment: {
    key: 'base_ac_mod_adjustment',
    label: 'Base AC Mod',
  },
};

/**
 * Flattened list of “Core Impact” children for presence checks.
 *
 * @example
 * hasAnyNonZeroAdjustment(adjustments, TOP_ADVANCED_CHILD_FIELDS)
 */
export const TOP_ADVANCED_CHILD_FIELDS: FieldDef[] = Object.values(
  TOP_ADVANCED_CHILD
)
  .filter((v): v is { key: NumericAdjustmentKey; label: string } => Boolean(v))
  .map(({ key, label }) => ({ key, label }));

/**
 * Attribute/stat adjustments.
 *
 * @example
 * <AdjustmentGroup adjustments={a} fields={STAT_FIELDS} />
 */
export const STAT_FIELDS: FieldDef[] = [
  { key: 'str_mod_adjustment', label: 'Strength Adjustment' },
  { key: 'dex_mod_adjustment', label: 'Dexterity Adjustment' },
  { key: 'int_mod_adjustment', label: 'Intelligence Adjustment' },
  { key: 'agi_mod_adjustment', label: 'Agility Adjustment' },
  { key: 'chr_mod_adjustment', label: 'Charisma Adjustment' },
  { key: 'dur_mod_adjustment', label: 'Durability Adjustment' },
  { key: 'focus_mod_adjustment', label: 'Focus Adjustment' },
];

/**
 * Affix Adjustments = Proc/Chance/Special + Mitigation/Reduction + Stacking/Irresistible.
 * Shown in Advanced mode (zeros render neutrally; nulls hidden).
 *
 * @example
 * <AdjustmentGroup adjustments={a} fields={AFFIX_ADJUSTMENT_FIELDS} />
 */
export const AFFIX_ADJUSTMENT_FIELDS: FieldDef[] = [
  { key: 'stackable_adjustment', label: 'Stackable Adjustment' },
  { key: 'non_stacking_adjustment', label: 'Non-Stacking Adjustment' },
  { key: 'irresistible_adjustment', label: 'Irresistible Adjustment' },
];

/**
 * Devouring fields (separate section with note).
 *
 * @example
 * <AdjustmentGroup adjustments={a} fields={DEVOURING_FIELDS} />
 */
export const DEVOURING_FIELDS: FieldDef[] = [
  { key: 'devouring_light_adjustment', label: 'Devouring Light' },
  { key: 'devouring_darkness_adjustment', label: 'Devouring Darkness' },
];
