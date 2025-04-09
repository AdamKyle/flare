import BaseInventoryItemDefinition from './base-inventory-item-definition';

export interface InventorySetDefinition {
  equippable: boolean;
  equipped: boolean;
  set_id: number;
  items: BaseInventoryItemDefinition[] | [];
}

export default interface SetDefinition {
  [key: string]: InventorySetDefinition;
}
