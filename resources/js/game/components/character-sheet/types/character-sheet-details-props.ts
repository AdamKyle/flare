import { AttackTypes } from '../enums/attack-types';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface CharacterSheetDetailsProps {
  openReincarnationSystem: () => void;
  openClassRanksSystem: () => void;
  openCharacterInventory: () => void;
  openSkillsSystem: () => void;
  characterData: CharacterSheetDefinition;
  showAttackType: boolean;
  attackType: AttackTypes | null;
}
