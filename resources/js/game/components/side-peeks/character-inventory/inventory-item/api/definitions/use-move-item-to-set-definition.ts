import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseMoveItemToSetRequestParams from './use-move-item-to-set-request-params';
import { StateSetter } from '../../../../../../../types/state-setter-type';

export default interface UseMoveItemToSetDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<UseMoveItemToSetRequestParams>;
}
