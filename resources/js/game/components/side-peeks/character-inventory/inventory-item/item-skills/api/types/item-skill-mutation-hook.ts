import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface ItemSkillMutationHook {
  mutate: (itemSkillProgressionId: number) => Promise<string | null>;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  reset_error: () => void;
}
