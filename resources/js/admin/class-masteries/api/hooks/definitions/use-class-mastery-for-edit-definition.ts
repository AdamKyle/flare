import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryFormDefinition from '../../definitions/class-mastery-form-definition';

export default interface UseClassMasteryForEditDefinition {
  class_mastery: ClassMasteryFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
