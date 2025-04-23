import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseMapApiDefinition from './base-map-api-definition';

export default interface BaseMapDetailsApiDefinition {
  data: BaseMapApiDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
