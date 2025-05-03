import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MoveCharacterRequestDefinition from './move-character-request-definition';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface UseMoveCharacterDirectionallyApiDefinition {
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<MoveCharacterRequestDefinition>;
}
