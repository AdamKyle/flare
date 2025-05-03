import { StateSetter } from '../../../../../../types/state-setter-type';
import CharacterMapPosition from '../../../types/character-map-position';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface MapDetailsApiRequestParams {
  characterData?: CharacterSheetDefinition | null;
  url: string;
  callback?: StateSetter<CharacterMapPosition>;
  handleResetMapMovement?: () => void;
}
