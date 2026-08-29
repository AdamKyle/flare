import NpcDefinition from '../../../api/definitions/npc-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface NpcFormSidePeekProps extends SidePeekProps {
  game_map_id: number;
  npc_id: number;
  on_saved: (npc: NpcDefinition) => void;
}
