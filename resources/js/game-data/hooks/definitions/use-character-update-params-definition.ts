import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';

export default interface UseCharacterUpdateParamsDefinition {
  userId: number;
  onEvent: (character: UseCharterUpdateStreamResponse) => void;
}
