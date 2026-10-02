import { resolveActiveGuideTarget } from './resolve-active-guide-target';
import { isOptionalGuideStepTarget } from './resolve-guide-step-target-wait';
import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';

export const resolveNextAvailableGuideStepIndex = (
  steps: GuideQuestWalkthroughStepDefinition[],
  startIndex: number
): number => {
  let nextIndex = startIndex;
  let nextStep = steps[nextIndex];

  while (
    nextStep?.target &&
    isOptionalGuideStepTarget(nextStep) &&
    !resolveActiveGuideTarget(nextStep.target)
  ) {
    nextIndex += 1;
    nextStep = steps[nextIndex];
  }

  return nextIndex;
};
