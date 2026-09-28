import GemAbilityFormOptionsDefinition from '../api/definitions/gem-ability-form-options-definition';
import GemAbilityFormErrorsDefinition from '../definitions/gem-ability-form-errors-definition';
import GemAbilityFormStateDefinition from '../definitions/gem-ability-form-state-definition';

export default interface GemAbilityFormFieldsProps {
  state: GemAbilityFormStateDefinition;
  errors: GemAbilityFormErrorsDefinition;
  form_options: GemAbilityFormOptionsDefinition;
  on_change: <K extends keyof GemAbilityFormStateDefinition>(
    field: K,
    value: GemAbilityFormStateDefinition[K]
  ) => void;
}
