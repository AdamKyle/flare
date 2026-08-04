import LabyrinthInventoryItemDefinition from './labyrinth-inventory-item-definition';
import LabyrinthOracleCostsDefinition from './labyrinth-oracle-costs-definition';

export default interface LabyrinthOracleApiResponseDefinition {
  inventory: LabyrinthInventoryItemDefinition[];
  costs: LabyrinthOracleCostsDefinition;
  message?: string;
}
