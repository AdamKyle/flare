import {
  ComparisonItemToEquip,
  ItemComparisonRow,
} from '../../../../../api-definitions/items/item-comparison-details';

export interface UseCompareItemApiResponseDefinition {
  details: ItemComparisonRow[];
  item_to_equip: ComparisonItemToEquip;
}
