import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapLocationSidePeekProps extends SidePeekProps {
  game_map_id: number;
  location_id: number;
  on_editor_changed: () => Promise<void> | void;
  on_move_requested: (location_id: number) => void;
}
