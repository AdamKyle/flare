import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportMapGemsDefinition {
  import_map_gems: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
