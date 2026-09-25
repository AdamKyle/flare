import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseBeginExplorationRequestParamsDefinition from './use-begin-exploration-request-params-definition';
import { StateSetter } from '../../../../../../../../types/state-setter-type';
import ExplorationMessageDefinition from '../../../../../../../api-definitions/chat/exploration-message-definition';

export default interface UseBeginExplorationApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  successMessage: string | null;
  explorationMessage: ExplorationMessageDefinition | null;
  setRequestParams: StateSetter<UseBeginExplorationRequestParamsDefinition>;
}
