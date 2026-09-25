import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseEmptySetApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  emptySet: (setId: number) => Promise<void>;
}
