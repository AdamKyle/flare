import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseChatStreamParams {
  characterData?: CharacterSheetDefinition | null;
  view_port?: number;
  is_automation_running?: boolean;
}
