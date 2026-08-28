import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapFormOptionsDefinition from '../../../definitions/game-map-form-options-definition';

export default interface UseGameMapFormOptionsDefinition {
  form_options: GameMapFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
