import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';

export default interface CraftActionPanelProps {
  selectedItem: CraftableItemDefinition | null;
  isCrafting: boolean;
  isCraftingDisabled: boolean;
  inventoryIsFull: boolean;
  isTimeoutActive: boolean;
  formattedRemaining: string;
  progress: number;
  onCraft: () => void;
}
