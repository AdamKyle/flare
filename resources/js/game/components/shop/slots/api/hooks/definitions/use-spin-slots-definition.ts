import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SpinSlotsResponseDefinition from '../../definitions/spin-slots-response-definition';

export default interface UseSpinSlotsDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  spin: () => Promise<SpinSlotsResponseDefinition | null>;
}
