import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemDetailDefinition from '../../definitions/location-gem-detail-definition';

export default interface UseActivateLocationGemRollDefinition {
  activating: boolean;
  error: AxiosErrorDefinition | null;
  activate_roll: (
    location_gem_id: number,
    gem_id: number
  ) => Promise<LocationGemDetailDefinition | null>;
}
