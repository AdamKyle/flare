import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestFormOptionsDefinition from '../../definitions/quest-form-options-definition';

export default interface UseQuestFormOptionsDefinition {
  form_options: QuestFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
