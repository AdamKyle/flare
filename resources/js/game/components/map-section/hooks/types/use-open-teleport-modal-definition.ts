import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseOpenTeleportModalDefinition {
  openTeleport: (
    character_data: CharacterSheetDefinition,
    character_x: number,
    character_y: number
  ) => void;
}
