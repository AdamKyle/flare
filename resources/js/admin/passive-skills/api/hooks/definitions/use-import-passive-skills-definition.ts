import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportPassiveSkillsDefinition {
  import_passive_skills: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
