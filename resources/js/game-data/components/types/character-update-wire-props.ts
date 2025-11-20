import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';

export default interface CharacterUpdateWireProps {
  userId: number;
  onEvent: (data: UseCharterUpdateStreamResponse) => void;
}
