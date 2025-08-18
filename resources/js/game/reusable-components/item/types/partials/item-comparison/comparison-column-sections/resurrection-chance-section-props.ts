import { ItemAdjustments } from '../../../../../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface ResurrectionChanceSectionProps {
  adjustments: ItemAdjustments;
  toEquipType: InventoryItemTypes;
}
