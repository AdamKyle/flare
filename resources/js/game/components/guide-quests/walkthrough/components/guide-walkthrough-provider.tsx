import React, { useCallback, useRef } from 'react';

import GuideWalkthroughProviderProps from './types/guide-walkthrough-provider-props';
import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import { GuideWalkthroughContext } from '../context/guide-walkthrough-context';
import { GuideWalkthroughAdvanceResult } from '../enums/guide-walkthrough-advance-result';
import { GuideWalkthroughStepType } from '../enums/guide-walkthrough-step-type';
import { useGuideTargetClickAdvancement } from '../hooks/use-guide-target-click-advancement';
import { useGuideWalkthroughDriver } from '../hooks/use-guide-walkthrough-driver';
import { useGuideWalkthroughEventAdvancement } from '../hooks/use-guide-walkthrough-event-advancement';
import { useGuideWalkthroughProgress } from '../hooks/use-guide-walkthrough-progress';
import { useGuideWalkthroughStepTarget } from '../hooks/use-guide-walkthrough-step-target';
import { useWalkthroughScrollLock } from '../hooks/use-walkthrough-scroll-lock';
import { resolveNextAvailableGuideStepIndex } from '../utils/resolve-next-available-guide-step-index';

const GuideWalkthroughProvider = ({
  children,
}: GuideWalkthroughProviderProps) => {
  const {
    steps,
    stepIndex,
    activeGuideQuestId,
    loading,
    error,
    loadWalkthrough,
    advanceToStep,
    resetWalkthrough,
    reportWalkthroughError,
  } = useGuideWalkthroughProgress();
  const { presentDriverStep, destroyDriver } = useGuideWalkthroughDriver();
  const { bindTargetClick, unbindTargetClick } =
    useGuideTargetClickAdvancement();
  const { bindStepEvent, unbindStepEvent } =
    useGuideWalkthroughEventAdvancement();

  const advancingRef = useRef(false);

  const activeStep =
    activeGuideQuestId === null ? null : (steps[stepIndex] ?? null);

  useWalkthroughScrollLock(activeGuideQuestId !== null);

  const cleanPresentation = useCallback((): void => {
    destroyDriver();
    unbindTargetClick();
    unbindStepEvent();
  }, [destroyDriver, unbindStepEvent, unbindTargetClick]);

  const closeWalkthrough = useCallback((): void => {
    cleanPresentation();
    resetWalkthrough();
    advancingRef.current = false;
  }, [cleanPresentation, resetWalkthrough]);

  const advance = useCallback(
    async (skipUnavailableOptionalSteps = false): Promise<void> => {
      if (activeGuideQuestId === null || advancingRef.current) {
        return;
      }

      advancingRef.current = true;
      cleanPresentation();

      const nextIndex = skipUnavailableOptionalSteps
        ? resolveNextAvailableGuideStepIndex(steps, stepIndex + 1)
        : stepIndex + 1;

      try {
        const result = await advanceToStep(nextIndex);

        if (
          result === GuideWalkthroughAdvanceResult.COMPLETED ||
          result === GuideWalkthroughAdvanceResult.FAILED
        ) {
          closeWalkthrough();
        }
      } finally {
        advancingRef.current = false;
      }
    },
    [
      activeGuideQuestId,
      advanceToStep,
      cleanPresentation,
      closeWalkthrough,
      stepIndex,
      steps,
    ]
  );

  const presentStep = useCallback(
    (
      step: GuideQuestWalkthroughStepDefinition,
      target: HTMLElement | null
    ): void => {
      const handleAdvance = () => void advance();

      presentDriverStep({
        step,
        element: target,
        on_continue: handleAdvance,
        on_close: closeWalkthrough,
      });

      if (step.step_type === GuideWalkthroughStepType.CLICK && target) {
        bindTargetClick(target, handleAdvance);
      }

      if (
        step.step_type === GuideWalkthroughStepType.EVENT &&
        step.required_event
      ) {
        bindStepEvent(step.required_event, handleAdvance);
      }
    },
    [
      advance,
      bindStepEvent,
      bindTargetClick,
      closeWalkthrough,
      presentDriverStep,
    ]
  );

  const skipOptionalStep = useCallback(
    (): Promise<void> => advance(true),
    [advance]
  );

  const failMissingTarget = useCallback((): void => {
    reportWalkthroughError(
      'This walkthrough target is not available. You can close the guide and keep playing.'
    );
    closeWalkthrough();
  }, [closeWalkthrough, reportWalkthroughError]);

  useGuideWalkthroughStepTarget({
    step: activeStep,
    on_target_resolved: presentStep,
    on_optional_target_missing: skipOptionalStep,
    on_required_target_missing: failMissingTarget,
    on_step_cleanup: cleanPresentation,
  });

  const startWalkthrough = useCallback(
    (guideQuestId: number): Promise<boolean> => {
      closeWalkthrough();

      return loadWalkthrough(guideQuestId);
    },
    [closeWalkthrough, loadWalkthrough]
  );

  return (
    <GuideWalkthroughContext.Provider
      value={{
        active_guide_quest_id: activeGuideQuestId,
        loading,
        error,
        startWalkthrough,
        pauseWalkthrough: closeWalkthrough,
        closeWalkthrough,
      }}
    >
      <div aria-live="polite" className="sr-only">
        {error}
      </div>
      {children}
    </GuideWalkthroughContext.Provider>
  );
};

export default GuideWalkthroughProvider;
