import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportLocationGemsDefinition {
  import_location_gems: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
