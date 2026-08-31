import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportQuestsDefinition {
  import_quests: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
