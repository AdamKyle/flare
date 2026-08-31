import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportMonstersDefinition {
  import_monsters: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
