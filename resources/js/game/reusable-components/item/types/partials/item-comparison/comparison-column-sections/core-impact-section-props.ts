import { ItemAdjustments } from '../../../../../../api-definitions/items/item-comparison-details';
import type { NumericAdjustmentKey } from '../../../item-comparison-types';

export default interface CoreImpactSectionProps {
  adjustments: ItemAdjustments;
  hasCoreTotals: boolean;
  showAdvancedChildUnderTop: boolean;
  forceCoreZeroKeys?: NumericAdjustmentKey[];
}
