import { resolveActiveGuideTarget } from './resolve-active-guide-target';
import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';

export const resolveVisibleGuideStepIndex = (
  steps: GuideQuestWalkthroughStepDefinition[],
  startIndex: number
): number | null => {
  for (let index = startIndex; index < steps.length; index += 1) {
    const target = steps[index]?.target;

    if (target && resolveActiveGuideTarget(target)) {
      return index;
    }
  }

  return null;
};
