import CharacterSheetDefinition from '../definitions/character-api-definitions/character-sheet-definition';

export default interface UseCharacterSheetApiState {
  data: CharacterSheetDefinition | null;
  error: Error | null;
  loading: boolean;
}
