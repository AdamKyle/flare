import MonsterFormOptionsDefinition from '../api/definitions/monster-form-options-definition';
import MonsterFormErrorsDefinition from '../definitions/monster-form-errors-definition';
import MonsterFormStateDefinition from '../definitions/monster-form-state-definition';

export default interface MonsterFormFieldsProps {
  state: MonsterFormStateDefinition;
  errors: MonsterFormErrorsDefinition;
  form_options: MonsterFormOptionsDefinition;
  on_change: <K extends keyof MonsterFormStateDefinition>(
    field: K,
    value: MonsterFormStateDefinition[K]
  ) => void;
}
