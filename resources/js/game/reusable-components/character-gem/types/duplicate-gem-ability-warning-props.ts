import DuplicateGemAbilityDefinition from '../../../api-definitions/gems/duplicate-gem-ability-definition';
import { DuplicateGemAbilityWarningContext } from '../enums/duplicate-gem-ability-warning-context';

export default interface DuplicateGemAbilityWarningProps {
  duplicates: DuplicateGemAbilityDefinition[];
  context: DuplicateGemAbilityWarningContext;
  on_open_item: (itemId: number) => void;
}
