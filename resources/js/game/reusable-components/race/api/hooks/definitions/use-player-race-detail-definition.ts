import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RaceDetailDefinition from '../../../types/race-detail-definition';

export default interface UsePlayerRaceDetailDefinition {
  race: RaceDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
