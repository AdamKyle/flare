import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemBulkRollResultDefinition from '../../definitions/map-gem-bulk-roll-result-definition';

export default interface UseRollAllMapGemsDefinition {
  rolling: boolean;
  error: AxiosErrorDefinition | null;
  roll_all: () => Promise<MapGemBulkRollResultDefinition | null>;
}
