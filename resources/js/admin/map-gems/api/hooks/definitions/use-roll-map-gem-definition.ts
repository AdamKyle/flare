import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemDetailDefinition from '../../definitions/map-gem-detail-definition';

export default interface UseRollMapGemDefinition {
  rolling: boolean;
  error: AxiosErrorDefinition | null;
  roll: (map_gem_id: number) => Promise<MapGemDetailDefinition | null>;
}
