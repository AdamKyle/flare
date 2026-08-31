import QuestFormOptionsDefinition from '../api/definitions/quest-form-options-definition';
import QuestFormErrorsDefinition from '../definitions/quest-form-errors-definition';
import QuestFormStateDefinition from '../definitions/quest-form-state-definition';

export default interface QuestFormFieldsProps {
  state: QuestFormStateDefinition;
  errors: QuestFormErrorsDefinition;
  form_options: QuestFormOptionsDefinition;
  on_change: <K extends keyof QuestFormStateDefinition>(
    field: K,
    value: QuestFormStateDefinition[K]
  ) => void;
}
