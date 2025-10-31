import UseMonsterUpdateStreamResponse from 'game-data/hooks/definitions/use-monster-update-stream-response';

export default interface UseMonsterUpdatesParams {
  userId: number;
  onEvent: (monsters: UseMonsterUpdateStreamResponse) => void;
}
