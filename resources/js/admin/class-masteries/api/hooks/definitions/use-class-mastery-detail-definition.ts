import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryDetailDefinition from '../../definitions/class-mastery-detail-definition';

export default interface UseClassMasteryDetailDefinition {
  class_mastery: ClassMasteryDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
