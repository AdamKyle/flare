import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseListItemOnMarketRequestParamsDefinition from './use-list-item-on-market-request-params-definition';
import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseListItemOnMarketDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<UseListItemOnMarketRequestParamsDefinition>;
}
