import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemBulkRollResultDefinition from '../../definitions/location-gem-bulk-roll-result-definition';

export default interface UseRollAllLocationGemsDefinition {
  rolling: boolean;
  error: AxiosErrorDefinition | null;
  roll_all: () => Promise<LocationGemBulkRollResultDefinition | null>;
}
