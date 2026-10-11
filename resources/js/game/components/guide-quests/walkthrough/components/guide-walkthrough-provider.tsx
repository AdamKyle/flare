import React, { useCallback, useRef } from 'react';

import GuideWalkthroughProviderProps from './types/guide-walkthrough-provider-props';
import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import { GuideWalkthroughContext } from '../context/guide-walkthrough-context';
import { GuideWalkthroughAdvanceResult } from '../enums/guide-walkthrough-advance-result';
import { GuideWalkthroughEvent } from '../enums/guide-walkthrough-event';
import { GuideWalkthroughInteraction } from '../enums/guide-walkthrough-interaction';
import { GuideWalkthroughInteractionSignal } from '../enums/guide-walkthrough-interaction-signal';
import { GuideWalkthroughStepType } from '../enums/guide-walkthrough-step-type';
import { useGuideTargetClickAdvancement } from '../hooks/use-guide-target-click-advancement';
import { useGuideWalkthroughDriver } from '../hooks/use-guide-walkthrough-driver';
import { useGuideWalkthroughEventAdvancement } from '../hooks/use-guide-walkthrough-event-advancement';
import { useGuideWalkthroughProgress } from '../hooks/use-guide-walkthrough-progress';
import { useGuideWalkthroughStepTarget } from '../hooks/use-guide-walkthrough-step-target';
import { useWalkthroughScrollLock } from '../hooks/use-walkthrough-scroll-lock';
import { GuideTarget } from '../registry/guide-targets';
import { resolveGuideStepInteraction } from '../utils/resolve-guide-step-interaction';
import { resolveNextAvailableGuideStepIndex } from '../utils/resolve-next-available-guide-step-index';
import { resolveVisibleGuideStepIndex } from '../utils/resolve-visible-guide-step-index';

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
  const targetCleanupRef = useRef(new Map<GuideTarget, Set<() => void>>());

  const activeStep =
    activeGuideQuestId === null ? null : (steps[stepIndex] ?? null);

  useWalkthroughScrollLock(activeGuideQuestId !== null);

  const cleanPresentation = useCallback((): void => {
    destroyDriver();
    unbindTargetClick();
    unbindStepEvent();
  }, [destroyDriver, unbindStepEvent, unbindTargetClick]);

  const registerTargetCleanup = useCallback(
    (target: GuideTarget, cleanup: () => void): (() => void) => {
      const cleanups = targetCleanupRef.current.get(target) ?? new Set();
      cleanups.add(cleanup);
      targetCleanupRef.current.set(target, cleanups);

      return () => {
        cleanups.delete(cleanup);

        if (cleanups.size === 0) {
          targetCleanupRef.current.delete(target);
        }
      };
    },
    []
  );

  const runTargetCleanup = useCallback(
    (
      step: GuideQuestWalkthroughStepDefinition | null,
      nextStep: GuideQuestWalkthroughStepDefinition | null
    ): void => {
      if (!step?.target || step.target === nextStep?.target) {
        return;
      }

      const interaction = resolveGuideStepInteraction(step);

      if (!interaction.cleanup_on_exit) {
        return;
      }

      targetCleanupRef.current
        .get(step.target)
        ?.forEach((cleanup) => cleanup());
    },
    []
  );

  const closeWalkthrough = useCallback((): void => {
    runTargetCleanup(activeStep, null);
    cleanPresentation();
    resetWalkthrough();
    advancingRef.current = false;
  }, [activeStep, cleanPresentation, resetWalkthrough, runTargetCleanup]);

  const advance = useCallback(
    async (
      nextIndexOverride?: number,
      skipUnavailableOptionalSteps = false
    ): Promise<void> => {
      if (activeGuideQuestId === null || advancingRef.current) {
        return;
      }

      advancingRef.current = true;

      const nextIndex =
        nextIndexOverride ??
        (skipUnavailableOptionalSteps
          ? resolveNextAvailableGuideStepIndex(steps, stepIndex + 1)
          : stepIndex + 1);
      const nextStep = steps[nextIndex] ?? null;

      runTargetCleanup(activeStep, nextStep);

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
      activeStep,
      advanceToStep,
      closeWalkthrough,
      stepIndex,
      steps,
      runTargetCleanup,
    ]
  );

  const completeTargetInteraction = useCallback(
    (target: GuideTarget, signal: GuideWalkthroughInteractionSignal): void => {
      if (!activeStep || activeStep.target !== target) {
        return;
      }

      const interaction = resolveGuideStepInteraction(activeStep);

      if (interaction.completion_signal !== signal) {
        return;
      }

      void advance();
    },
    [activeStep, advance]
  );

  const completeRequiredEvent = useCallback(
    async (event: GuideWalkthroughEvent): Promise<boolean> => {
      if (
        !activeStep ||
        activeStep.step_type !== GuideWalkthroughStepType.EVENT ||
        activeStep.required_event !== event
      ) {
        return false;
      }

      await advance();

      return true;
    },
    [activeStep, advance]
  );

  const presentStep = useCallback(
    (
      step: GuideQuestWalkthroughStepDefinition,
      target: HTMLElement | null
    ): void => {
      const interaction = resolveGuideStepInteraction(step);

      if (
        target?.dataset.guideTargetComplete === 'true' &&
        (interaction.interaction ===
          GuideWalkthroughInteraction.OPEN_AND_SELECT ||
          interaction.interaction === GuideWalkthroughInteraction.SIMPLE_CLICK)
      ) {
        void advance();

        return;
      }

      const handleAdvance = () => void advance();

      presentDriverStep({
        step,
        element: target,
        on_continue: handleAdvance,
        on_close: closeWalkthrough,
      });

      if (
        interaction.interaction === GuideWalkthroughInteraction.SIMPLE_CLICK &&
        step.step_type === GuideWalkthroughStepType.CLICK &&
        target
      ) {
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
    (): Promise<void> => advance(undefined, true),
    [advance]
  );

  const failMissingTarget = useCallback((): void => {
    if (
      activeStep?.target &&
      resolveGuideStepInteraction(activeStep).skip_when_target_missing
    ) {
      const visibleStepIndex = resolveVisibleGuideStepIndex(
        steps,
        stepIndex + 1
      );

      if (visibleStepIndex !== null) {
        void advance(visibleStepIndex);

        return;
      }
    }

    reportWalkthroughError(
      'This walkthrough target is not available. You can close the guide and keep playing.'
    );
    closeWalkthrough();
  }, [
    activeStep,
    advance,
    closeWalkthrough,
    reportWalkthroughError,
    stepIndex,
    steps,
  ]);

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
        active_target: activeStep?.target ?? null,
        loading,
        error,
        startWalkthrough,
        pauseWalkthrough: closeWalkthrough,
        closeWalkthrough,
        completeTargetInteraction,
        completeRequiredEvent,
        registerTargetCleanup,
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
