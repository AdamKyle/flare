import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseGetSetEquippabilityRequestParams from './use-get-set-equippability-request-params';
import UseGetSetEquippabilityResponse from './use-get-set-equippability-response-definition';
import { StateSetter } from '../../../../../../../types/state-setter-type';

export default interface UseGetSetEquippabilityDefinition {
  data: UseGetSetEquippabilityResponse[] | null;
  error: AxiosErrorDefinition | null;
  setRequestParams: StateSetter<UseGetSetEquippabilityRequestParams>;
  loading: boolean;
}
