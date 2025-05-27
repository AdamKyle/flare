import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import TeleportCharacterRequestDefinition from './teleport-character-request-definition';
import { StateSetter } from '../../../../../../../../types/state-setter-type';

export interface UseTeleportPlayerApiDefinition {
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<TeleportCharacterRequestDefinition>;
}
