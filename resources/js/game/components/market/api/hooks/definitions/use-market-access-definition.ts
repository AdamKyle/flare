import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseMarketAccessDefinition {
  can_access_market: boolean;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
