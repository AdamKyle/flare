import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import CharacterAttackBreakDownDefinition from '../../definitions/character-attack-break-down-definition';

export default interface UseGetCharacterStatBreakdownDefinition {
  data: CharacterAttackBreakDownDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
