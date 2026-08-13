import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import FetchSetSailPortsResponseDefinition from '../../definitions/fetch-set-sail-ports-response-definition';

export default interface UseFetchSetSailPortsApiDefinition {
  data: FetchSetSailPortsResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
