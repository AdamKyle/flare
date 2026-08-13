import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import FetchCelestialOptionsResponseDefinition from '../../definitions/fetch-celestial-options-response-definition';

export default interface UseFetchCelestialOptionsApiDefinition {
  data: FetchCelestialOptionsResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
