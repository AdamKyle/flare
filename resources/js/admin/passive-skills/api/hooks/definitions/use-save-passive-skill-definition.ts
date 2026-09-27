import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import PassiveSkillFormDefinition from '../../definitions/passive-skill-form-definition';
import PassiveSkillRequestDefinition from '../../definitions/passive-skill-request-definition';

export default interface UseSavePassiveSkillDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    passive_skill_id: number | null,
    payload: PassiveSkillRequestDefinition
  ) => Promise<PassiveSkillFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
