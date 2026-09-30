import DuplicateGemAbilityDefinition from '../../../api-definitions/gems/duplicate-gem-ability-definition';

export default interface DuplicateGemAbilityWarningProps {
  duplicates: DuplicateGemAbilityDefinition[];
  context: 'item' | 'set';
  on_open_item: (itemId: number) => void;
}
