import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GameMapEditorDefinition from '../../definitions/game-map-editor-definition';

export default interface UseGameMapEditorDefinition {
  editor: GameMapEditorDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
