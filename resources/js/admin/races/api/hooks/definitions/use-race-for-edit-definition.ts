import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RaceDefinition from '../../definitions/race-definition';

export default interface UseRaceForEditDefinition {
  race: RaceDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
