import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ItemUsageDefinition from '../../api/definitions/item-usage-definition';

export default interface ItemUsageCardProps {
  usage: ItemUsageDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
  on_open_related_entity?: (resource: string, id: number) => void;
}
