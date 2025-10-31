import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseListenForMapNameChangeParams {
  character_data: CharacterSheetDefinition | null;
  updateCharacterData: (character: Partial<CharacterSheetDefinition>) => void;
}
