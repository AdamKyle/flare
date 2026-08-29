import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportItemsDefinition {
  import_items: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
