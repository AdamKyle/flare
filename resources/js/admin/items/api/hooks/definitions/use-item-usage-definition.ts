import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemUsageDefinition from '../../definitions/item-usage-definition';

export default interface UseItemUsageDefinition {
  usage: ItemUsageDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  refresh: () => void;
}
