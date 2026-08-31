import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestFormDefinition from '../../definitions/quest-form-definition';

export default interface UseQuestForEditDefinition {
  quest: QuestFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
