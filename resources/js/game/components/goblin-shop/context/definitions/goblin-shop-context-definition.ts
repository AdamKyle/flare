import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface GoblinShopContextDefinition {
  data: BaseUsableItemDefinition[];
  loading: boolean;
  error: AxiosErrorDefinition | null;
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
}
