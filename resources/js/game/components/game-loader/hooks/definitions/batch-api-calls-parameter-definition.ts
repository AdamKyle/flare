import { BatchApiCallKey } from '../enums/batch-api-call-key';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';

export default interface BatchApiCallsParameterDefinition {
  api_call: () => Promise<CharacterSheetDefinition | MonsterDefinition[]>;
  key: BatchApiCallKey;
  progress_step: number;
}
