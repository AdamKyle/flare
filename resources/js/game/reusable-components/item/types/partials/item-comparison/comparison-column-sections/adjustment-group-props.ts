import { ItemAdjustments } from '../../../../../../api-definitions/items/item-comparison-details';

export type NumericAdjustmentKey = Exclude<
  keyof ItemAdjustments,
  'skill_summary'
>;

export interface FieldDef {
  key: NumericAdjustmentKey;
  label: string;
}

export default interface AdjustmentGroupProps {
  adjustments: ItemAdjustments;
  fields: FieldDef[];
  showAdvancedChild?: boolean;
  forceShowZeroKeys?: NumericAdjustmentKey[];
}
