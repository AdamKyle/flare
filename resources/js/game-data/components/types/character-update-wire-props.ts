import UseCharacterBoonsUpdateStreamResponse from 'game-data/hooks/definitions/use-character-boons-update-stream-response';
import UseCharterUpdateStreamResponse from 'game-data/hooks/definitions/use-character-update-stream-response';
import UseGemProgressionUpdateStreamResponse from 'game-data/hooks/definitions/use-gem-progression-update-stream-response';
import UseLocationBasedCraftingOptionsStreamResponse from 'game-data/hooks/definitions/use-location-based-crafting-options-stream-response';

export default interface CharacterUpdateWireProps {
  userId: number;
  onEvent: (data: UseCharterUpdateStreamResponse) => void;
  onCraftingOptionsEvent: (
    data: UseLocationBasedCraftingOptionsStreamResponse
  ) => void;
  onBoonsEvent: (data: UseCharacterBoonsUpdateStreamResponse) => void;
  onGemProgressionEvent: (data: UseGemProgressionUpdateStreamResponse) => void;
}
