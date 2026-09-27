import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import SkillFormDefinition from '../../definitions/skill-form-definition';
import SkillRequestDefinition from '../../definitions/skill-request-definition';

export default interface UseSaveSkillDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    skill_id: number | null,
    payload: SkillRequestDefinition
  ) => Promise<SkillFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
