import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassFormDefinition from '../../definitions/class-form-definition';

export default interface UseClassForEditDefinition {
  game_class: ClassFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
