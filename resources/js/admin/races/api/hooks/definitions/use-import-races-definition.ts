import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportRacesDefinition {
  import_races: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
