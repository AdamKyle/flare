import LabyrinthInventoryItemDefinition from '../../api/definitions/labyrinth-inventory-item-definition';
export default interface TransferItemSelectionProps {
  inventory: LabyrinthInventoryItemDefinition[];
  sourceId: number | null;
  destinationId: number | null;
  onSource: (id: number) => void;
  onDestination: (id: number) => void;
}
