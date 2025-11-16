import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseStoreGuideQuestRequestDefinition from './use-store-guide-quest-request-definition';
import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseStoreGuideQuestContentDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  canMoveForward: boolean;
  setRequestParams: StateSetter<UseStoreGuideQuestRequestDefinition>;
}
