import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SendPublicEntityCommandRequest from './send-public-entity-command-request';

export default interface UseSendPublicEntityCommandDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  send_public_entity_command: (
    request: SendPublicEntityCommandRequest
  ) => Promise<boolean>;
}
