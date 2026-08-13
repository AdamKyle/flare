import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import CelestialStatsResponseDefinition from '../../definitions/celestial-stats-response-definition';

export default interface UseFetchCelestialStatsApiDefinition {
  data: CelestialStatsResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  fetchCelestialStats: (monsterId: number) => Promise<void>;
}
