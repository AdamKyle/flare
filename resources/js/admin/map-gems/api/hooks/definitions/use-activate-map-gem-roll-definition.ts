import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemDetailDefinition from '../../definitions/map-gem-detail-definition';

export default interface UseActivateMapGemRollDefinition {
  activating: boolean;
  error: AxiosErrorDefinition | null;
  activate_roll: (
    map_gem_id: number,
    gem_id: number
  ) => Promise<MapGemDetailDefinition | null>;
}
