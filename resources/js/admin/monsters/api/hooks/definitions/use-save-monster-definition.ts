import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MonsterFormDefinition from '../../definitions/monster-form-definition';

export default interface UseSaveMonsterDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    monsterId: number | null,
    payload: Record<string, unknown>
  ) => Promise<MonsterFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
