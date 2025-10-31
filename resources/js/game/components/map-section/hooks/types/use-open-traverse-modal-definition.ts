import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseOpenTraverseModalDefinition {
  openTraverse: (character_data: CharacterSheetDefinition) => void;
}
