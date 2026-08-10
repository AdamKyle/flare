import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseFetchTraversableMapsResponse from './use-fetch-traversable-maps-response';

export default interface UseFetchTraversableMapsDefinition {
  data: UseFetchTraversableMapsResponse[] | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
