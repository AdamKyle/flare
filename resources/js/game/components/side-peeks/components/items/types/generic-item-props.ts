import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface GenericItemProps {
  character: CharacterSheetDefinition;
  update_character?: (character: Partial<CharacterSheetDefinition>) => void;
  on_switch_view: (value: boolean) => void;
}
