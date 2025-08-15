import { ItemComparisonRow } from '../../../api-definitions/items/item-comparison-details';

export default interface ItemComparisonProps {
  comparisonDetails: ItemComparisonRow[] | [];
  item_name: string;
}
