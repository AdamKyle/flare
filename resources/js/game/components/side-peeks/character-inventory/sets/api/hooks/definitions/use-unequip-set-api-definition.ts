import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseUnequipSetApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  unequipSet: () => Promise<void>;
}
