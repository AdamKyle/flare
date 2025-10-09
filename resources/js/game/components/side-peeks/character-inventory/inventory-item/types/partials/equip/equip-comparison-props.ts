import { ItemComparisonRow } from '../../../../../../../api-definitions/items/item-comparison-details';

export default interface EquipComparisonProps {
  comparison_data?: ItemComparisonRow;
  item_name: string;
  comparison_index: number;
}
