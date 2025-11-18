import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseChatStreamParams {
  character_data?: CharacterSheetDefinition | null;
}
