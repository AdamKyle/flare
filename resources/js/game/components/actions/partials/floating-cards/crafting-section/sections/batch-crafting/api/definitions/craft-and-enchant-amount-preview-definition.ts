import BatchCraftingDestinationCapacityDefinition from './batch-crafting-destination-capacity-definition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';

export interface CraftAndEnchantAffixPreviewDefinition {
  id: number;
  name: string;
  cost: number;
  int_required: number;
}

export default interface CraftAndEnchantAmountPreviewDefinition {
  item_id: number | null;
  item_name: string | null;
  requested_amount: number;
  prefix: CraftAndEnchantAffixPreviewDefinition | null;
  suffix: CraftAndEnchantAffixPreviewDefinition | null;
  crafting_gold_cost_each: number;
  enchanting_gold_cost_each: number;
  total_gold_cost_each: number;
  total_requested_gold_cost: number;
  gold_available: number;
  disposition: string | null;
  output_destination: BatchCraftingOutputDestination | null;
  output_set_id: number | null;
  output_set_name: string | null;
  listing_price: number | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
  blockers: string[];
}
