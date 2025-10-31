import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseMapApiDefinition from './base-map-api-definition';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface BaseMapDetailsApiDefinition {
  data: BaseMapApiDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRefresh: StateSetter<boolean>;
}
