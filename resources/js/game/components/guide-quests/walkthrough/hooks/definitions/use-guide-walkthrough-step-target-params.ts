import { GuideQuestWalkthroughStepDefinition } from '../../../api/definitions/guide-quest-walkthrough-definition';

export default interface UseGuideWalkthroughStepTargetParams {
  step: GuideQuestWalkthroughStepDefinition | null;
  on_target_resolved: (
    step: GuideQuestWalkthroughStepDefinition,
    target: HTMLElement | null
  ) => void;
  on_optional_target_missing: () => Promise<void>;
  on_required_target_missing: () => void;
  on_step_cleanup: () => void;
}
