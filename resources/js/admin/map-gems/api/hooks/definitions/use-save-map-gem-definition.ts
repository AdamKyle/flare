import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemFormDefinition from '../../definitions/map-gem-form-definition';
import MapGemRequestDefinition from '../../definitions/map-gem-request-definition';

export default interface UseSaveMapGemDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    map_gem_id: number | null,
    payload: MapGemRequestDefinition
  ) => Promise<MapGemFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
