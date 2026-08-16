import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';

export interface BatchCraftingPreviewItemDefinition {
  id: number;
  name: string;
}

export interface BatchCraftingDestinationCapacityDefinition {
  current: number;
  max: number;
  remaining: number;
}

export default interface BatchCraftingPreviewDefinition {
  item: BatchCraftingPreviewItemDefinition | null;
  requested_amount: number;
  unit_cost: number;
  total_cost: number;
  available_gold: number;
  gold_after_purchase: number;
  can_afford: boolean;
  output_destination: BatchCraftingOutputDestination | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
  can_fit: boolean;
  maximum_request_amount: number;
  blockers: string[];
}
