import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestDefinition, {
  GuideQuestWalkthroughStepDefinition,
} from '../../api/definitions/guide-quest-definition';
import GuideQuestFormErrorsDefinition from '../../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../../definitions/guide-quest-form-state-definition';

export default interface UseManageGuideQuestFormDefinition {
  form_state: GuideQuestFormStateDefinition;
  field_errors: GuideQuestFormErrorsDefinition;
  update_field: <K extends keyof GuideQuestFormStateDefinition>(
    field: K,
    value: GuideQuestFormStateDefinition[K]
  ) => void;
  update_walkthrough_steps: (
    steps: GuideQuestWalkthroughStepDefinition[]
  ) => void;
  validate_step: (stepIndex: number) => boolean;
  submit: () => Promise<GuideQuestDefinition | null>;
  is_saving: boolean;
  save_error: AxiosErrorDefinition | null;
}
