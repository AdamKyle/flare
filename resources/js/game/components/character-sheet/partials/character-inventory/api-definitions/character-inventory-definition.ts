import BaseInventoryItemDefinition from './base-inventory-item-definition';
import SavableSetDefinition from './savable-sets-definition';
import SetDefinition from './set-definition';

export default interface CharacterInventoryDefinition {
  inventory: BaseInventoryItemDefinition[] | [];
  quest_items: BaseInventoryItemDefinition[] | [];
  equipped: BaseInventoryItemDefinition[] | [];
  usable_items: BaseInventoryItemDefinition[] | [];
  savable_sets: SavableSetDefinition[] | [];
  usable_sets: SavableSetDefinition[] | [];
  sets: SetDefinition;
  set_is_equipped: boolean;
  set_name_equipped: string | null;
}
