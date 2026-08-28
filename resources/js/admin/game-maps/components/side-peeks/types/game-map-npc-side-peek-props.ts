import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapNpcSidePeekProps extends SidePeekProps {
  game_map_id: number;
  npc_id: number;
  on_editor_changed: () => Promise<void> | void;
  on_move_requested: (npc_id: number) => void;
}
