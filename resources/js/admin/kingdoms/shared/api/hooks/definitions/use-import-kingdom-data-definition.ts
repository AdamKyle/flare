import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportKingdomDataDefinition {
  import_kingdom_data: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
