import { ItemComparisonRow } from '../../../../../api-definitions/items/item-comparison-details';

export default interface ItemComparisonColumnProps {
  row: ItemComparisonRow;
  showAdvanced: boolean;
  showAdvancedChildUnderTop: boolean;
  showHeaderSection?: boolean;
}
