import { ItemComparisonRow } from '../../../../../api-definitions/items/item-comparison-details';

export default interface ItemComparisonColumnProps {
  row: ItemComparisonRow;
  heading?: string;
  index: number;
  showAdvanced: boolean;
  showAdvancedChildUnderTop: boolean;
}
