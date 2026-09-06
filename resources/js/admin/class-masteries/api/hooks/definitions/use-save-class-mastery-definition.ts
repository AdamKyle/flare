import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassMasteryFormDefinition from '../../definitions/class-mastery-form-definition';
import ClassMasteryRequestDefinition from '../../definitions/class-mastery-request-definition';

export default interface UseSaveClassMasteryDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    class_mastery_id: number | null,
    payload: ClassMasteryRequestDefinition
  ) => Promise<ClassMasteryFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
