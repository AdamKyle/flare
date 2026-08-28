import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapFormOptionsDefinition from '../../definitions/game-map-form-options-definition';
import GameMapFormErrorsDefinition from '../../definitions/game-map-form-errors-definition';
import GameMapFormResponseDefinition from '../../definitions/game-map-form-response-definition';
import GameMapFormStateDefinition from '../../definitions/game-map-form-state-definition';

export default interface UseGameMapFormDefinition {
  form_state: GameMapFormStateDefinition;
  update_field: <K extends keyof GameMapFormStateDefinition>(
    field: K,
    value: GameMapFormStateDefinition[K]
  ) => void;
  form_options: GameMapFormOptionsDefinition | null;
  current_map_url: string | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: GameMapFormErrorsDefinition;
  request_next: (step_index: number) => boolean;
  submit: () => Promise<GameMapFormResponseDefinition | null>;
  clear_errors: () => void;
}
