import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseCompareItemApiRequestParameters {
  characterData?: CharacterSheetDefinition | null;
  url: string;
  item_name: string;
  item_type: string;
}
