import { ItemAdjustments } from '../../../../../api-definitions/items/item-comparison-details';
import { FieldDef, NumericAdjustmentKey } from '../../item-comparison-types';

export default interface AdjustmentGroupProps {
  adjustments: ItemAdjustments;
  fields: FieldDef[];
  showAdvancedChild?: boolean;
  forceShowZeroKeys?: NumericAdjustmentKey[];
}
