import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import { GuideWalkthroughInteraction } from '../enums/guide-walkthrough-interaction';
import { GuideWalkthroughStepType } from '../enums/guide-walkthrough-step-type';
import {
  GuideTargets,
  resolveGuideTargetInteraction,
} from '../registry/guide-targets';
import GuideTargetInteractionDefinition from '../types/guide-target-interaction-definition';

const resolveDefaultInteraction = (
  stepType: GuideWalkthroughStepType
): GuideWalkthroughInteraction => {
  if (stepType === GuideWalkthroughStepType.CLICK) {
    return GuideWalkthroughInteraction.SIMPLE_CLICK;
  }

  if (stepType === GuideWalkthroughStepType.EVENT) {
    return GuideWalkthroughInteraction.EVENT;
  }

  return GuideWalkthroughInteraction.CONTINUE;
};

export const resolveGuideStepInteraction = (
  step: GuideQuestWalkthroughStepDefinition
): GuideTargetInteractionDefinition => {
  const targetInteraction = step.target
    ? resolveGuideTargetInteraction(step.target)
    : null;

  if (!targetInteraction) {
    return { interaction: resolveDefaultInteraction(step.step_type) };
  }

  if (
    step.target === GuideTargets.ITEM_ACTIONS &&
    step.step_type === GuideWalkthroughStepType.CONTINUE
  ) {
    return {
      ...targetInteraction,
      interaction: GuideWalkthroughInteraction.CONTINUE,
      completion_signal: undefined,
    };
  }

  return targetInteraction;
};
