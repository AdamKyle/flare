import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationDefinition from '../../definitions/location-definition';
import MoveLocationRequestDefinition from '../../definitions/move-location-request-definition';

export default interface UseMoveLocationDefinition {
  moving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  move: (
    game_map_id: number,
    location_id: number,
    request: MoveLocationRequestDefinition
  ) => Promise<LocationDefinition | null>;
  clear_error: () => void;
}
