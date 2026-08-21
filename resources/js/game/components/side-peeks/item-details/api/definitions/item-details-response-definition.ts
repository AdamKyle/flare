import ItemColorFieldsDefinition from '../../../../character-sheet/partials/character-inventory/styles/item-color-fields-definition';
import StatModifiersDefinition from '../../../character-inventory/inventory-item/types/partials/item-view/stat-modifiers-definition';

export default interface ItemDetailsResponseDefinition
  extends ItemColorFieldsDefinition, StatModifiersDefinition {
  id: number;
  name: string;
  description: string;
  raw_damage: number;
  base_damage_mod: number;
  raw_ac: number | null;
  base_ac: number;
  base_ac_mod: number | null;
  raw_healing: number | null;
  base_healing: number;
  base_healing_mod: number | null;
  ambush_chance: number;
  ambush_resistance_chance: number;
  counter_chance: number;
  counter_resistance_chance: number;
}
