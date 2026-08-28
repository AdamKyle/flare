import NpcDefinition from '../api/definitions/npc-definition';

export default interface NpcFormScreenProps {
  game_map_id: number;
  npc_id: number | null;
  initial_x: number | null;
  initial_y: number | null;
  on_saved: (npc: NpcDefinition) => void;
  on_cancel: () => void;
  embedded?: boolean;
}
