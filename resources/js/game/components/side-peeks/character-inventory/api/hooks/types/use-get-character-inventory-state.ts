import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import CharacterInventoryDefinition from '../../../api-definitions/character-inventory-definition';

export default interface UseGetCharacterInventoryState {
  data: CharacterInventoryDefinition | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
