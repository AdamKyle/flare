import { AxiosError } from 'axios';
import { Driver } from 'driver.js';
import { useEventSystem } from 'event-system/hooks/use-event-system';
import React, {
  ReactNode,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';

import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import { useGuideQuestWalkthroughApi } from '../../api/hooks/use-guide-quest-walkthrough-api';
import { GuideWalkthroughContext } from '../context/guide-walkthrough-context';
import { presentGuideStep } from '../driver/driver-adapter';
import { GuideWalkthroughEvent } from '../enums/guide-walkthrough-event';
import { GuideWalkthroughStepType } from '../enums/guide-walkthrough-step-type';
import GuideWalkthroughEventMap from '../events/guide-walkthrough-event-map';
import { GUIDE_WALKTHROUGH_EVENT_EMITTER } from '../events/guide-walkthrough-events';
import { useWalkthroughScrollLock } from '../hooks/use-walkthrough-scroll-lock';
import {
  isAsyncGuideTarget,
  isStateDependentGuideTarget,
} from '../registry/guide-targets';
import {
  resolveActiveGuideTarget,
  waitForActiveGuideTarget,
} from '../utils/resolve-active-guide-target';

import { useGameData } from 'game-data/hooks/use-game-data';

interface GuideWalkthroughProviderProps {
  children: ReactNode;
}

const OPTIONAL_TARGET_WAIT_MS = 400;
const ASYNC_TARGET_WAIT_MS = 3000;

const GuideWalkthroughProvider = ({
  children,
}: GuideWalkthroughProviderProps) => {
  const { gameData } = useGameData();
  const userId = gameData?.character?.user_id ?? 0;
  const { fetchWalkthrough, saveProgress } =
    useGuideQuestWalkthroughApi(userId);
  const eventSystem = useEventSystem();
  const eventEmitter = useMemo(
    () =>
      eventSystem.fetchOrCreateEventEmitter<GuideWalkthroughEventMap>(
        GUIDE_WALKTHROUGH_EVENT_EMITTER
      ),
    [eventSystem]
  );
  const driverRef = useRef<Driver | null>(null);
  const targetRef = useRef<HTMLElement | null>(null);
  const clickHandlerRef = useRef<(() => void) | null>(null);
  const eventHandlerRef = useRef<(() => void) | null>(null);
  const eventNameRef = useRef<GuideWalkthroughEvent | null>(null);
  const advancingRef = useRef(false);
  const [steps, setSteps] = useState<GuideQuestWalkthroughStepDefinition[]>([]);
  const [stepIndex, setStepIndex] = useState(0);
  const [activeGuideQuestId, setActiveGuideQuestId] = useState<number | null>(
    null
  );
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useWalkthroughScrollLock(activeGuideQuestId !== null);

  const cleanPresentation = useCallback(() => {
    driverRef.current?.destroy();
    driverRef.current = null;

    if (targetRef.current && clickHandlerRef.current) {
      targetRef.current.removeEventListener('click', clickHandlerRef.current);
    }

    if (eventNameRef.current && eventHandlerRef.current) {
      eventEmitter.off(eventNameRef.current, eventHandlerRef.current);
    }

    targetRef.current = null;
    clickHandlerRef.current = null;
    eventHandlerRef.current = null;
    eventNameRef.current = null;
  }, [eventEmitter]);

  const closeWalkthrough = useCallback(() => {
    cleanPresentation();
    setActiveGuideQuestId(null);
    setSteps([]);
    setStepIndex(0);
    advancingRef.current = false;
  }, [cleanPresentation]);

  const advance = useCallback(
    async (skipUnavailableOptionalSteps = false) => {
      if (activeGuideQuestId === null || advancingRef.current) {
        return;
      }

      advancingRef.current = true;
      cleanPresentation();

      let nextIndex = stepIndex + 1;
      let nextStep = steps[nextIndex];

      if (skipUnavailableOptionalSteps) {
        while (
          nextStep?.target &&
          (nextStep.optional_if_target_missing ||
            isStateDependentGuideTarget(nextStep.target)) &&
          !resolveActiveGuideTarget(nextStep.target)
        ) {
          nextIndex += 1;
          nextStep = steps[nextIndex];
        }
      }

      try {
        if (!nextStep) {
          await saveProgress(activeGuideQuestId, null, true);
          closeWalkthrough();
          return;
        }

        await saveProgress(activeGuideQuestId, nextStep.id, false);
        setStepIndex(nextIndex);
      } finally {
        advancingRef.current = false;
      }
    },
    [
      activeGuideQuestId,
      cleanPresentation,
      closeWalkthrough,
      saveProgress,
      stepIndex,
      steps,
    ]
  );

  useEffect(() => {
    const step = steps[stepIndex];

    if (!step || activeGuideQuestId === null) {
      return;
    }

    let cancelled = false;

    const present = async (): Promise<void> => {
      const targetIsOptional =
        !!step.target &&
        (step.optional_if_target_missing ||
          isStateDependentGuideTarget(step.target));
      const targetWaitMs = step.target
        ? isAsyncGuideTarget(step.target)
          ? ASYNC_TARGET_WAIT_MS
          : targetIsOptional
            ? OPTIONAL_TARGET_WAIT_MS
            : undefined
        : undefined;
      const target = step.target
        ? await waitForActiveGuideTarget(step.target, targetWaitMs)
        : null;

      if (cancelled) {
        return;
      }

      if (step.target && !target) {
        if (targetIsOptional) {
          await advance(true);
          return;
        }

        setError(
          'This walkthrough target is not available. You can close the guide and keep playing.'
        );
        closeWalkthrough();
        return;
      }

      targetRef.current = target;
      driverRef.current = presentGuideStep({
        step,
        element: target,
        on_continue: () => void advance(),
        on_close: closeWalkthrough,
      });

      if (step.step_type === GuideWalkthroughStepType.CLICK && target) {
        const handleClick = () => void advance();
        clickHandlerRef.current = handleClick;
        target.addEventListener('click', handleClick, { once: true });
      }

      if (
        step.step_type === GuideWalkthroughStepType.EVENT &&
        step.required_event
      ) {
        const handleEvent = () => void advance();
        eventHandlerRef.current = handleEvent;
        eventNameRef.current = step.required_event;
        eventEmitter.on(step.required_event, handleEvent);
      }
    };

    void present();

    return () => {
      cancelled = true;

      cleanPresentation();
    };
  }, [
    activeGuideQuestId,
    advance,
    cleanPresentation,
    closeWalkthrough,
    eventEmitter,
    stepIndex,
    steps,
  ]);

  const startWalkthrough = useCallback(
    async (guideQuestId: number): Promise<boolean> => {
      closeWalkthrough();
      setLoading(true);
      setError(null);

      try {
        const walkthrough = await fetchWalkthrough(guideQuestId);
        const firstStep = walkthrough.steps[0];

        if (!firstStep) {
          setError('This walkthrough has no steps.');
          return false;
        }

        await saveProgress(guideQuestId, firstStep.id, false);
        setSteps(walkthrough.steps);
        setStepIndex(0);
        setActiveGuideQuestId(guideQuestId);

        return true;
      } catch (requestError) {
        if (requestError instanceof AxiosError) {
          setError(
            requestError.response?.data?.message ??
              'Unable to start this walkthrough.'
          );
        }

        return false;
      } finally {
        setLoading(false);
      }
    },
    [closeWalkthrough, fetchWalkthrough, saveProgress]
  );

  useEffect(() => cleanPresentation, [cleanPresentation]);

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
