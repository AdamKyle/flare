import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import ClassFormDefinition from '../../definitions/class-form-definition';
import ClassRequestDefinition from '../../definitions/class-request-definition';

export default interface UseSaveClassDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    class_id: number | null,
    payload: ClassRequestDefinition
  ) => Promise<ClassFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
