import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';

export default interface CraftResultAlertProps {
  characterId: number;
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  craftedInventorySlotId: number | null;
  craftedItemDetails: CraftableItemDefinition | null;
}
