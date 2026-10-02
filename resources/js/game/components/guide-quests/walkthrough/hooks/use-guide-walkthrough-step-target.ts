import { useEffect } from 'react';

import UseGuideWalkthroughStepTargetParams from './definitions/use-guide-walkthrough-step-target-params';
import { waitForActiveGuideTarget } from '../utils/resolve-active-guide-target';
import {
  isOptionalGuideStepTarget,
  resolveGuideStepTargetWaitMs,
} from '../utils/resolve-guide-step-target-wait';

export const useGuideWalkthroughStepTarget = ({
  step,
  on_target_resolved,
  on_optional_target_missing,
  on_required_target_missing,
  on_step_cleanup,
}: UseGuideWalkthroughStepTargetParams): void => {
  useEffect(() => {
    if (!step) {
      return;
    }

    const controller = new AbortController();

    const resolveStepTarget = async (): Promise<void> => {
      const target = step.target
        ? await waitForActiveGuideTarget(
            step.target,
            resolveGuideStepTargetWaitMs(step),
            controller.signal
          )
        : null;

      if (controller.signal.aborted) {
        return;
      }

      if (step.target && !target) {
        if (isOptionalGuideStepTarget(step)) {
          await on_optional_target_missing();

          return;
        }

        on_required_target_missing();

        return;
      }

      on_target_resolved(step, target);
    };

    void resolveStepTarget();

    return () => {
      controller.abort();

      on_step_cleanup();
    };
  }, [
    on_optional_target_missing,
    on_required_target_missing,
    on_step_cleanup,
    on_target_resolved,
    step,
  ]);
};
