import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassFormOptionsDefinition from '../../definitions/class-form-options-definition';

export default interface UseClassFormOptionsDefinition {
  form_options: ClassFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
