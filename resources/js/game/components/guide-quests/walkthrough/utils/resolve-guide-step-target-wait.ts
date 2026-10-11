import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import {
  isAsyncGuideTarget,
  isStateDependentGuideTarget,
  resolveGuideTargetInteraction,
} from '../registry/guide-targets';

const OPTIONAL_TARGET_WAIT_MS = 400;
const ASYNC_TARGET_WAIT_MS = 3000;

export const isOptionalGuideStepTarget = (
  step: GuideQuestWalkthroughStepDefinition
): boolean => {
  if (!step.target) {
    return false;
  }

  return (
    step.optional_if_target_missing || isStateDependentGuideTarget(step.target)
  );
};

export const resolveGuideStepTargetWaitMs = (
  step: GuideQuestWalkthroughStepDefinition
): number | undefined => {
  if (!step.target) {
    return undefined;
  }

  if (isAsyncGuideTarget(step.target)) {
    return ASYNC_TARGET_WAIT_MS;
  }

  if (resolveGuideTargetInteraction(step.target)?.skip_when_target_missing) {
    return OPTIONAL_TARGET_WAIT_MS;
  }

  if (isOptionalGuideStepTarget(step)) {
    return OPTIONAL_TARGET_WAIT_MS;
  }

  return undefined;
};
