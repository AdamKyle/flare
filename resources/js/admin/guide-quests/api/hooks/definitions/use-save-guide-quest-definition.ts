import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestFormErrorsDefinition from '../../../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../../../definitions/guide-quest-form-state-definition';
import GuideQuestDefinition from '../../definitions/guide-quest-definition';

export default interface UseSaveGuideQuestDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: GuideQuestFormErrorsDefinition;
  save: (
    guideQuestId: number,
    state: GuideQuestFormStateDefinition
  ) => Promise<GuideQuestDefinition | null>;
  clear_field_error: (field: keyof GuideQuestFormStateDefinition) => void;
}
