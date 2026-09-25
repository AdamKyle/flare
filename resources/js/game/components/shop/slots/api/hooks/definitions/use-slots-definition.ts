import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SlotsResponseDefinition from '../../definitions/slots-response-definition';

export default interface UseSlotsDefinition {
  data: SlotsResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refetch: () => void;
}
