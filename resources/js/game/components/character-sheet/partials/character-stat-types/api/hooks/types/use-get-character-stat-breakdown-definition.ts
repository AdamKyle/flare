import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import CharacterStatBreakDownDefinition from '../../../api-definitions/character-stat-break-down-definition';

export default interface UseGetCharacterStatBreakdownDefinition {
  data: CharacterStatBreakDownDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
