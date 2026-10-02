import DuplicateGemAbilityDefinition, {
  DuplicateGemAbilityItemDefinition,
} from '../../../api-definitions/gems/duplicate-gem-ability-definition';

export interface DuplicateGemAbilitySetGroup {
  set_id: number | null;
  set_name: string | null;
  items: DuplicateGemAbilityItemDefinition[];
}

export default interface PreparedDuplicateGemAbility {
  ability: DuplicateGemAbilityDefinition['ability'];
  equipped_items: DuplicateGemAbilityItemDefinition[];
  inventory_items: DuplicateGemAbilityItemDefinition[];
  set_groups: DuplicateGemAbilitySetGroup[];
}
