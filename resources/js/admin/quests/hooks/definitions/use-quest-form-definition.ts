import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestFormDefinition from '../../api/definitions/quest-form-definition';
import { useQuestFormOptions } from '../../api/hooks/use-quest-form-options';
import QuestFormErrorsDefinition from '../../definitions/quest-form-errors-definition';
import QuestFormStateDefinition from '../../definitions/quest-form-state-definition';

type QuestFormStateFieldKey = Extract<keyof QuestFormStateDefinition, string>;

export default interface UseQuestFormDefinition {
  form_state: QuestFormStateDefinition;
  update_field: <K extends QuestFormStateFieldKey>(
    field: K,
    value: QuestFormStateDefinition[K]
  ) => void;
  form_options: ReturnType<typeof useQuestFormOptions>['form_options'];
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: QuestFormErrorsDefinition;
  submit: () => Promise<QuestFormDefinition | null>;
  validate_step: (stepIndex: number) => boolean;
}
