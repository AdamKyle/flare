import BatchCraftingDestinationCapacityDefinition from './batch-crafting-destination-capacity-definition';
import { CraftAndEnchantAffixPreviewDefinition } from './craft-and-enchant-amount-preview-definition';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';

export interface CraftAndEnchantSetPreviewPositionDefinition {
  position: string;
  item_name: string;
  prefix: CraftAndEnchantAffixPreviewDefinition | null;
  suffix: CraftAndEnchantAffixPreviewDefinition | null;
  crafting_cost: number;
  enchanting_cost: number;
  combined_cost: number;
}

export default interface CraftAndEnchantSetPreviewDefinition {
  included_position_count: number;
  positions: CraftAndEnchantSetPreviewPositionDefinition[];
  crafting_gold_total: number;
  enchanting_gold_total: number;
  total_gold_cost: number;
  gold_available: number;
  disposition: string | null;
  output_destination: BatchCraftingOutputDestination | null;
  output_set_id: number | null;
  output_set_name: string | null;
  listing_price: number | null;
  destination_capacity: BatchCraftingDestinationCapacityDefinition | null;
  can_fit: boolean;
  blockers: string[];
}
