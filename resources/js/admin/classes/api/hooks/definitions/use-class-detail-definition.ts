import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassDetailDefinition from '../../definitions/class-detail-definition';

export default interface UseClassDetailDefinition {
  game_class: ClassDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
