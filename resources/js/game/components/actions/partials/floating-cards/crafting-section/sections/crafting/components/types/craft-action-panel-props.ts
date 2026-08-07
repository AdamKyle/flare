import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';

export default interface CraftActionPanelProps {
  selectedItem: CraftableItemDefinition | null;
  inventoryIsFull: boolean;
  isCraftSuccessful: boolean;
  onViewCraftedItem?: () => void;
}
