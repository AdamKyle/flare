import BatchCraftingDestinationCapacityDefinition from '../../api/definitions/batch-crafting-destination-capacity-definition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';

export default interface CraftAmountProgressProps {
  requested_amount: number;
  completed_amount: number;
  output_destination: BatchCraftingOutputDestination | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
}
