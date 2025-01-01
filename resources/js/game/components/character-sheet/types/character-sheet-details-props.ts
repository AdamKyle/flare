import { AttackTypes } from '../enums/attack-types';
import { StatTypes } from '../enums/stat-types';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface CharacterSheetDetailsProps {
  openReincarnationSystem: () => void;
  openClassRanksSystem: () => void;
  openCharacterInventory: () => void;
  characterData: CharacterSheetDefinition;
  showAttackType: boolean;
  attackType: AttackTypes | null;
  showStatType: boolean;
  statType: StatTypes | null;
}
