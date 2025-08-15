import { ItemAdjustments } from '../../../api-definitions/items/item-comparison-details';

/**
 * Keys on ItemAdjustments whose values are numeric (number | null | undefined).
 *
 * @example
 * type NumericKey = NumericAdjustmentKey; // "str_mod_adjustment" | "total_damage_adjustment" | ...
 */
export type NumericAdjustmentKey = {
  [K in keyof ItemAdjustments]: ItemAdjustments[K] extends
    | number
    | null
    | undefined
    ? K
    : never;
}[keyof ItemAdjustments];

/**
 * Field definition used by renderers to map keys to labels.
 *
 * @example
 * const strengthField: FieldDef = { key: 'str_mod_adjustment', label: 'Strength Adjustment' };
 */
export type FieldDef = { key: NumericAdjustmentKey; label: string };
