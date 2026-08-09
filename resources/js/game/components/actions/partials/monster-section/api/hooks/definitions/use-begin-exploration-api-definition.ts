import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseBeginExplorationRequestParamsDefinition from './use-begin-exploration-request-params-definition';
import { StateSetter } from '../../../../../../../../types/state-setter-type';

export default interface UseBeginExplorationApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  successMessage: string | null;
  setRequestParams: StateSetter<UseBeginExplorationRequestParamsDefinition>;
}
