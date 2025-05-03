import { StateSetter } from '../../../../../../types/state-setter-type';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import CharacterMapPosition from '../../../types/character-map-position';

export default interface MapDetailsApiRequestParams {
  characterData?: CharacterSheetDefinition | null;
  url: string;
  callback?: StateSetter<CharacterMapPosition>;
  handleResetMapMovement?: () => void;
}
