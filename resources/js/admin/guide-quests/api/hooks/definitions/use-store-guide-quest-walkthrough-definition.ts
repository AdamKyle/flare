import { GuideQuestWalkthroughStepDefinition } from '../../definitions/guide-quest-definition';

export default interface UseStoreGuideQuestWalkthroughDefinition {
  error: string | null;
  loading: boolean;
  storeWalkthrough: (
    guideQuestId: number,
    steps: GuideQuestWalkthroughStepDefinition[]
  ) => Promise<boolean>;
}
