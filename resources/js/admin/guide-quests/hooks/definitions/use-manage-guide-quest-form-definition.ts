import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GuideQuestDefinition, {
  GuideQuestWalkthroughStepDefinition,
} from '../../api/definitions/guide-quest-definition';

export default interface UseManageGuideQuestFormDefinition {
  handleNextStep: (currentIndex: number) => Promise<boolean>;
  setStepData: (step: number, data: Partial<GuideQuestDefinition>) => void;
  setWalkthroughSteps: (steps: GuideQuestWalkthroughStepDefinition[]) => void;
  isSaving: boolean;
  formError: AxiosErrorDefinition | null;
}
