import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassDetailDefinition from '../../../types/class-detail-definition';

export default interface UsePlayerClassDetailDefinition {
  game_class: ClassDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
