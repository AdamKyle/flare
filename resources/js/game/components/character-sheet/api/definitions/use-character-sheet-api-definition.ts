import CharacterSheetDefinition from './character-api-definitions/character-sheet-definition';

export default interface UseCharacterSheetApiDefinition {
  data: CharacterSheetDefinition | null;
  error: Error | null;
  loading: boolean;
}
