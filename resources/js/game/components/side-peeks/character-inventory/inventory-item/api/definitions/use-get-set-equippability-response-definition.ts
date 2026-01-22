import { InventoryItemTypes } from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';

export default interface UseGetSetEquippabilityResponse {
  type: InventoryItemTypes;
  count: number;
}
