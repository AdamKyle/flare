import QueenCostsDefinition from './queen-costs-definition';
import QueenInventorySlotDefinition from './queen-inventory-slot-definition';
import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface QueenOfHeartsApiResponseDefinition {
  unique_slots: QueenInventorySlotDefinition[];
  non_unique_slots: QueenInventorySlotDefinition[];
  costs: QueenCostsDefinition;
  message?: string;
  result_preview?: CraftingItemPreviewDefinition | null;
  source_result_preview?: CraftingItemPreviewDefinition | null;
  destination_result_preview?: CraftingItemPreviewDefinition | null;
}
