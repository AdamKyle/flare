import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemFormDefinition from '../../definitions/location-gem-form-definition';
import LocationGemRequestDefinition from '../../definitions/location-gem-request-definition';

export default interface UseSaveLocationGemDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    location_gem_id: number | null,
    payload: LocationGemRequestDefinition
  ) => Promise<LocationGemFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
