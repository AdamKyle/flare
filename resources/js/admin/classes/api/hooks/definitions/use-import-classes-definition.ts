import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportClassesDefinition {
  import_classes: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
