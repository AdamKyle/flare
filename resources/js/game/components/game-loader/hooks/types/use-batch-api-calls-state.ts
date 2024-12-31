import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameDataDefinition from 'game-data/deffinitions/game-data-definition';

export interface BatchApiCallsState {
  loading: boolean;
  progress: number;
  error: AxiosErrorDefinition | null;
  data: GameDataDefinition;
}
