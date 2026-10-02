import { GuideQuestWalkthroughStepDefinition } from '../../../api/definitions/guide-quest-walkthrough-definition';
import { GuideWalkthroughAdvanceResult } from '../../enums/guide-walkthrough-advance-result';

export default interface UseGuideWalkthroughProgressDefinition {
  steps: GuideQuestWalkthroughStepDefinition[];
  stepIndex: number;
  activeGuideQuestId: number | null;
  loading: boolean;
  error: string | null;
  loadWalkthrough: (guideQuestId: number) => Promise<boolean>;
  advanceToStep: (nextIndex: number) => Promise<GuideWalkthroughAdvanceResult>;
  resetWalkthrough: () => void;
  reportWalkthroughError: (message: string) => void;
}
