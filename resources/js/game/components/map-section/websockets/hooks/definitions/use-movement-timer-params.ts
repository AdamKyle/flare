import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseMovementTimerParams {
  characterData: CharacterSheetDefinition | null | undefined;
}
