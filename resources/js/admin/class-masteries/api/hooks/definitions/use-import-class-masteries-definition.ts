import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportClassMasteriesDefinition {
  import_class_masteries: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
