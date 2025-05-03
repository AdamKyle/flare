import { StateSetter } from '../../../../../../types/state-setter-type';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseBaseMapDetailsParams {
  characterData?: CharacterSheetDefinition | null;
  url: string;
  callback?: StateSetter<{ x: number; y: number }>;
}
