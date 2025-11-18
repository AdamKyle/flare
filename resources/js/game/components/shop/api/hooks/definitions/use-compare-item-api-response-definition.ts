import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { ItemComparisonRow } from '../../../../../api-definitions/items/item-comparison-details';

export interface UseCompareItemApiResponseDefinition {
  details: ItemComparisonRow[] | [];
  item_to_equip: EquippableItemWithBase;
}
