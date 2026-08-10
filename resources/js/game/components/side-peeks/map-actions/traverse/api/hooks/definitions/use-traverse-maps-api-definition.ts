import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseTraverseMapsApiResponse from './use-traverse-maps-api-response';
import UseTraverseMapsRequestParamsDefinition from './use-traverse-maps-request-params-definition';
import { StateSetter } from '../../../../../../../../types/state-setter-type';

export default interface UseTraverseMapsApiDefinition {
  data: UseTraverseMapsApiResponse | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: StateSetter<UseTraverseMapsRequestParamsDefinition>;
}
