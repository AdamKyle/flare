import ItemColorFieldsDefinition from '../../components/character-sheet/partials/character-inventory/styles/item-color-fields-definition';
import { DuplicateGemAbilitySource } from '../../reusable-components/character-gem/enums/duplicate-gem-ability-source';

export interface DuplicateGemAbilityItemDefinition extends ItemColorFieldsDefinition {
  item_id: number;
  name: string;
  source: DuplicateGemAbilitySource;
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
