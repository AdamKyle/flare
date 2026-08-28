import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcDefinition from '../../definitions/npc-definition';
import NpcRequestDefinition from '../../definitions/npc-request-definition';

export default interface UseSaveNpcDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    game_map_id: number,
    npc_id: number | null,
    request: NpcRequestDefinition
  ) => Promise<NpcDefinition | null>;
  clear_field_error: (field: string) => void;
}
