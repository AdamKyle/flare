import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestDefinition from '../../definitions/guide-quest-definition';

export default interface UseGuideQuestDetailDefinition {
  guide_quest: GuideQuestDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
