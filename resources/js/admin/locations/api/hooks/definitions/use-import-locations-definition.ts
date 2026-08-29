import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportLocationsDefinition {
  import_locations: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
