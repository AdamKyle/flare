import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryFormOptionsDefinition from '../../definitions/class-mastery-form-options-definition';

export default interface UseClassMasteryFormOptionsDefinition {
  form_options: ClassMasteryFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
