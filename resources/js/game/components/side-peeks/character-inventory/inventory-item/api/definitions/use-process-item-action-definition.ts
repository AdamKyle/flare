import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseProcessItemActionRequestParams from './use-process-item-action-request-params-definition';
import { StateSetter } from '../../../../../../../types/state-setter-type';

export default interface UseProcessItemActionDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestData: StateSetter<UseProcessItemActionRequestParams>;
  resetError: () => void;
}
