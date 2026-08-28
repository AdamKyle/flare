import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapFormResponseDefinition from '../../../definitions/game-map-form-response-definition';

export default interface UseSaveGameMapDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    game_map_id: number | null,
    form_data: FormData
  ) => Promise<GameMapFormResponseDefinition | null>;
  clear_field_error: (field: string) => void;
}
