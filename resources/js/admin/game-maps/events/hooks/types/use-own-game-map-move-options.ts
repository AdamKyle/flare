export default interface UseOwnGameMapMoveOptions {
  game_map_id: number;
  on_move_succeeded: () => Promise<void>;
}
