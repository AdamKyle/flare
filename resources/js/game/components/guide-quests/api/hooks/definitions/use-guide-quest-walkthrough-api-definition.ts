import GuideQuestWalkthroughDefinition from '../../definitions/guide-quest-walkthrough-definition';

export default interface UseGuideQuestWalkthroughApiDefinition {
  fetchWalkthrough: (
    guideQuestId: number
  ) => Promise<GuideQuestWalkthroughDefinition>;
  saveProgress: (
    guideQuestId: number,
    stepId: number | null,
    completed: boolean
  ) => Promise<void>;
  abortWalkthroughRequests: () => void;
}
