import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ConjureCelestialRequestDefinition from '../../definitions/conjure-celestial-request-definition';

export default interface UseConjureCelestialApiDefinition {
  conjureCelestial: (
    request: ConjureCelestialRequestDefinition
  ) => Promise<void>;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
