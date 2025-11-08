import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestResponseDefinition from '../../definitions/guide-quest-response-defintion';

export default interface UseFetchGuideQuestsDefinition {
  data: GuideQuestResponseDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
