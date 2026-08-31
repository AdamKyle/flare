import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminGameMapDetailSidePeekProps extends SidePeekProps {
  game_map_id: number;
  on_game_map_changed?: () => void;
}
