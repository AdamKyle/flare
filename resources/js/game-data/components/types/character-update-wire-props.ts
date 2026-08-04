import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';
import UseLocationBasedCraftingOptionsStreamResponse from 'game-data/hooks/definitions/use-location-based-crafting-options-stream-response';

export default interface CharacterUpdateWireProps {
  userId: number;
  onEvent: (data: UseCharterUpdateStreamResponse) => void;
  onCraftingOptionsEvent: (
    data: UseLocationBasedCraftingOptionsStreamResponse
  ) => void;
}
