import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseEquipItemRequestParamsDefinition from './use-equip-item-request-params-definition';
import { StateSetter } from '../../../../../../../types/state-setter-type';

export default interface UseEquipItemApiDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: StateSetter<UseEquipItemRequestParamsDefinition>;
}
