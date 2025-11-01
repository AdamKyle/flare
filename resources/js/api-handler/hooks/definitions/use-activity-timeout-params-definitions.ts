import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { AxiosError } from 'axios';

import { StateSetter } from '../../../types/state-setter-type';

export default interface UseActivityTimeoutParams {
  response: AxiosError;
  setError: StateSetter<AxiosErrorDefinition | null>;
}
