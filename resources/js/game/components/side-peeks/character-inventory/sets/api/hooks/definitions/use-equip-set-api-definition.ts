import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseEquipSetApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  equipSet: (setId: number) => Promise<void>;
}
