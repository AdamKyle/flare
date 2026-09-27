import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UnitFormDefinition from '../../definitions/unit-form-definition';

export default interface UseUnitForEditDefinition {
  unit: UnitFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
