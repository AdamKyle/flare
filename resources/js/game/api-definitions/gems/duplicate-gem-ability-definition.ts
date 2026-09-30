import ItemColorFieldsDefinition from '../../components/character-sheet/partials/character-inventory/styles/item-color-fields-definition';

export interface DuplicateGemAbilityItemDefinition extends ItemColorFieldsDefinition {
  item_id: number;
  name: string;
  source: 'inventory' | 'equipped' | 'set';
  set_id: number | null;
  set_name: string | null;
}

export default interface DuplicateGemAbilityDefinition {
  ability: {
    id: number;
    name: string;
  };
  count: number;
  items: DuplicateGemAbilityItemDefinition[];
}
