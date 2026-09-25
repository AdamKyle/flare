import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import AdminChatHistoryResponseDefinition from '../../definitions/admin-chat-history-response-definition';

export default interface UseFetchAdminChatHistoryDefinition {
  data: AdminChatHistoryResponseDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
