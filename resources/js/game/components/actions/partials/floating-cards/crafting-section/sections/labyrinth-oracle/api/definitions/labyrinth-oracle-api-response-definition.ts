import LabyrinthInventoryItemDefinition from './labyrinth-inventory-item-definition';
import LabyrinthOracleCostsDefinition from './labyrinth-oracle-costs-definition';
import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface LabyrinthOracleApiResponseDefinition {
  inventory: LabyrinthInventoryItemDefinition[];
  costs: LabyrinthOracleCostsDefinition;
  message?: string;
  source_result_preview?: CraftingItemPreviewDefinition | null;
  destination_result_preview?: CraftingItemPreviewDefinition | null;
}
