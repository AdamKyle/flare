import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { ItemComparisonRow } from '../../../../../../api-definitions/items/item-comparison-details';

export default interface UseGetInventoryItemComparisonDetailsResponseDefinition {
  details: ItemComparisonRow[] | [];
  itemToEquip: EquippableItemWithBase;
}
