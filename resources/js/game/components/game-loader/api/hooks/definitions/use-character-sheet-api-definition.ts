import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseCharacterSheetApiDefinition {
  fetchCharacterData: () => Promise<CharacterSheetDefinition>;
}
