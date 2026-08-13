import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseOpenConjureSidePeekDefinition {
  openConjure: (character_data: CharacterSheetDefinition) => void;
}
