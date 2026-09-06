import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemDetailDefinition from '../../definitions/location-gem-detail-definition';

export default interface UseRollLocationGemDefinition {
  rolling: boolean;
  error: AxiosErrorDefinition | null;
  roll: (
    location_gem_id: number
  ) => Promise<LocationGemDetailDefinition | null>;
}
