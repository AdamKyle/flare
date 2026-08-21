import BatchCraftingDestinationCapacityDefinition from './batch-crafting-destination-capacity-definition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';

export interface CraftSetPreviewPositionDefinition {
  position: string;
  item_id: number;
  crafting_type: string;
  item_name: string;
}

export default interface CraftSetPreviewDefinition {
  included_position_count: number;
  total_position_count: number;
  positions: CraftSetPreviewPositionDefinition[];
  total_cost: number;
  available_gold: number;
  can_afford: boolean;
  output_destination: BatchCraftingOutputDestination | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
  can_fit: boolean;
  blockers: string[];
}
