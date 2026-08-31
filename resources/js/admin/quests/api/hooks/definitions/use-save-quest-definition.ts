import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import QuestFormDefinition from '../../definitions/quest-form-definition';

export default interface UseSaveQuestDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    questId: number | null,
    payload: Record<string, unknown>
  ) => Promise<QuestFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
