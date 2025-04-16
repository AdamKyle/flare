import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseInventoryItemDefinition from '../../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface UseCharacterEquippedApiDefinition {
  data: BaseInventoryItemDefinition[];
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
