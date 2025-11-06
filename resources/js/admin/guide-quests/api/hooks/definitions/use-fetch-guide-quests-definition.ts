import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestDefinition from '../../definitions/guide-quest-definition';

export default interface UseFetchGuideQuestsDefinition {
  data: GuideQuestDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
