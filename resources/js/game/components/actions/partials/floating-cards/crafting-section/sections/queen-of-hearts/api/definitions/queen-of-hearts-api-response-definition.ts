import QueenCostsDefinition from './queen-costs-definition';
import QueenInventorySlotDefinition from './queen-inventory-slot-definition';

export default interface QueenOfHeartsApiResponseDefinition {
  unique_slots: QueenInventorySlotDefinition[];
  non_unique_slots: QueenInventorySlotDefinition[];
  costs: QueenCostsDefinition;
  message?: string;
}
