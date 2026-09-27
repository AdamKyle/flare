import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UnitDetailDefinition from '../../definitions/unit-detail-definition';

export default interface UseUnitDetailDefinition {
  unit: UnitDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
