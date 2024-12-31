import CharacterSheetDefinition from '../api-data-definitions/character/character-sheet-definition';
import MonsterDefinition from '../api-data-definitions/monsters/monster-definition';

export default interface GameDataDefinition {
  character: CharacterSheetDefinition | null;
  monsters: MonsterDefinition[] | [];
}
