import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseOpenSetSailSidePeekDefinition {
  openSetSail: (character_data: CharacterSheetDefinition) => void;
}
