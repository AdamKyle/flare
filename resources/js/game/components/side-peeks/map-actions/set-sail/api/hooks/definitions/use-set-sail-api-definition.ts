import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SetSailRequestDefinition from '../../definitions/set-sail-request-definition';

export default interface UseSetSailApiDefinition {
  setSail: (request: SetSailRequestDefinition) => Promise<void>;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
