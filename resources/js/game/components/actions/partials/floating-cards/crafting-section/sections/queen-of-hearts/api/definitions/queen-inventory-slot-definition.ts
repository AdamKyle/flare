import QueenInventoryItemDefinition from './queen-inventory-item-definition';

export default interface QueenInventorySlotDefinition {
  id: number;
  item_id: number;
  item: QueenInventoryItemDefinition;
}
