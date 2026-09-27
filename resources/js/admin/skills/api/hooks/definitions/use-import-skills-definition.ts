import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportSkillsDefinition {
  import_skills: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
