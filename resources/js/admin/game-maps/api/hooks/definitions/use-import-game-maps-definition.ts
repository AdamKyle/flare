import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseImportGameMapsDefinition {
  import_game_maps: (file: File) => Promise<boolean>;
  importing: boolean;
  error: AxiosErrorDefinition | null;
}
