import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestDetailDefinition from '../../definitions/guide-quest-detail-definition';

export default interface UseGuideQuestDetailDefinition {
  guide_quest: GuideQuestDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
