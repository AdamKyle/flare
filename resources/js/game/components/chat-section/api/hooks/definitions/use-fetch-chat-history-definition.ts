import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { ChatHistoryDataDefinition } from './chat-history-data-definition';

export interface UseFetchChatHistoryDefinition {
  data: ChatHistoryDataDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
