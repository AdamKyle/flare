import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MoveNpcRequestDefinition from '../../definitions/move-npc-request-definition';
import NpcDefinition from '../../definitions/npc-definition';

export default interface UseMoveNpcDefinition {
  moving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  move: (
    game_map_id: number,
    npc_id: number,
    request: MoveNpcRequestDefinition
  ) => Promise<NpcDefinition | null>;
  clear_error: () => void;
}
