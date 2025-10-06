import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UsePurchaseAndReplaceApiParams {
  character_id: number;
  on_success: (
    successMessage: string,
    character: Partial<CharacterSheetDefinition>
  ) => void;
}
