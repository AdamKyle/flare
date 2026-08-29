import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportNpcsDefinition {
  import_npcs: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
