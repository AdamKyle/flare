import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationDefinition from '../../definitions/location-definition';
import LocationRequestDefinition from '../../definitions/location-request-definition';

export default interface UseSaveLocationDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    game_map_id: number,
    location_id: number | null,
    request: LocationRequestDefinition
  ) => Promise<LocationDefinition | null>;
  clear_field_error: (field: string) => void;
}
