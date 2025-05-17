import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import TeleportCoordinatesApiDefinition from './teleport-coordinates-api-definition';

export default interface UseFetchTeleportCoordinatesApiDefinition {
  error: AxiosErrorDefinition | null;
  data: TeleportCoordinatesApiDefinition | null;
  loading: boolean;
}
