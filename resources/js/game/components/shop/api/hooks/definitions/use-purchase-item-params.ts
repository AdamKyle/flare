import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UsePurchaseItemParams {
  character_id: number;
  on_success: (character: Partial<CharacterSheetDefinition>) => void;
}
