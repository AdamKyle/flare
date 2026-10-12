import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseStoreGuideQuestRequestDefinition from './use-store-guide-quest-request-definition';

export default interface UseStoreGuideQuestContentDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  storeGuideQuestContent: (
    request: UseStoreGuideQuestRequestDefinition
  ) => Promise<boolean>;
}
