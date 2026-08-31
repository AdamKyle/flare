import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestDetailDefinition from '../../../../../game/reusable-components/quest/api/definitions/quest-detail-definition';

export default interface UseQuestDetailDefinition {
  quest: QuestDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
