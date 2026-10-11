import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import UseGuideWalkthroughProgressDefinition from './definitions/use-guide-walkthrough-progress-definition';
import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';
import { useGuideQuestWalkthroughApi } from '../../api/hooks/use-guide-quest-walkthrough-api';
import { GuideWalkthroughAdvanceResult } from '../enums/guide-walkthrough-advance-result';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useGuideWalkthroughProgress =
  (): UseGuideWalkthroughProgressDefinition => {
    const { gameData } = useGameData();
    const userId = gameData?.character?.user_id ?? 0;
    const { fetchWalkthrough, saveProgress, abortWalkthroughRequests } =
      useGuideQuestWalkthroughApi(userId);

    const [steps, setSteps] = useState<GuideQuestWalkthroughStepDefinition[]>(
      []
    );
    const [stepIndex, setStepIndex] = useState(0);
    const [activeGuideQuestId, setActiveGuideQuestId] = useState<number | null>(
      null
    );
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const loadRequestRef = useRef(0);

    const resetWalkthrough = useCallback((): void => {
      loadRequestRef.current += 1;
      abortWalkthroughRequests();

      setLoading(false);
      setActiveGuideQuestId(null);
      setSteps([]);
      setStepIndex(0);
    }, [abortWalkthroughRequests]);

    const loadWalkthrough = useCallback(
      async (guideQuestId: number): Promise<boolean> => {
        loadRequestRef.current += 1;
        const loadRequest = loadRequestRef.current;

        setLoading(true);
        setError(null);

        try {
          const walkthrough = await fetchWalkthrough(guideQuestId);
          const firstStep = walkthrough.steps[0];

          if (loadRequestRef.current !== loadRequest) {
            return false;
          }

          if (!firstStep) {
            setError('This walkthrough has no steps.');

            return false;
          }

          await saveProgress(guideQuestId, firstStep.id, false);

          if (loadRequestRef.current !== loadRequest) {
            return false;
          }

          setSteps(walkthrough.steps);
          setStepIndex(0);
          setActiveGuideQuestId(guideQuestId);

          return true;
        } catch (requestError) {
          if (axios.isCancel(requestError)) {
            return false;
          }

          if (loadRequestRef.current !== loadRequest) {
            return false;
          }

          setError(
            resolveApiErrorMessage(
              requestError,
              'Unable to start this walkthrough.'
            )
          );

          return false;
        } finally {
          if (loadRequestRef.current === loadRequest) {
            setLoading(false);
          }
        }
      },
      [fetchWalkthrough, saveProgress]
    );

    const advanceToStep = useCallback(
      async (nextIndex: number): Promise<GuideWalkthroughAdvanceResult> => {
        if (activeGuideQuestId === null) {
          return GuideWalkthroughAdvanceResult.CANCELLED;
        }

        const nextStep = steps[nextIndex];

        console.log('[GuideWalkthrough] advanceToStep: start', {
          nextIndex,
          nextStepId: nextStep?.id ?? null,
          nextStepTitle: nextStep?.title ?? null,
        });

        try {
          if (!nextStep) {
            await saveProgress(activeGuideQuestId, null, true);

            return GuideWalkthroughAdvanceResult.COMPLETED;
          }

          console.log('[GuideWalkthrough] advanceToStep: before saveProgress', {
            nextIndex,
            nextStepId: nextStep.id,
            nextStepTitle: nextStep.title,
          });

          await saveProgress(activeGuideQuestId, nextStep.id, false);

          console.log('[GuideWalkthrough] advanceToStep: after saveProgress', {
            nextIndex,
            nextStepId: nextStep.id,
          });

          console.log('[GuideWalkthrough] advanceToStep: before flushSync', {
            nextIndex,
            nextStepId: nextStep.id,
            nextStepTitle: nextStep.title,
          });

          flushSync(() => {
            console.log(
              '[GuideWalkthrough] advanceToStep: inside flushSync before setStepIndex',
              {
                nextIndex,
              }
            );
            setStepIndex(nextIndex);
            console.log(
              '[GuideWalkthrough] advanceToStep: inside flushSync after setStepIndex',
              {
                nextIndex,
              }
            );
          });

          console.log('[GuideWalkthrough] advanceToStep: after flushSync', {
            nextIndex,
            nextStepId: nextStep.id,
            nextStepTitle: nextStep.title,
          });

          return GuideWalkthroughAdvanceResult.ADVANCED;
        } catch (requestError) {
          if (axios.isCancel(requestError)) {
            return GuideWalkthroughAdvanceResult.CANCELLED;
          }

          setError(
            resolveApiErrorMessage(
              requestError,
              'Unable to save your walkthrough progress.'
            )
          );

          return GuideWalkthroughAdvanceResult.FAILED;
        }
      },
      [activeGuideQuestId, saveProgress, steps]
    );

    const reportWalkthroughError = useCallback((message: string): void => {
      setError(message);
    }, []);

    return {
      steps,
      stepIndex,
      activeGuideQuestId,
      loading,
      error,
      loadWalkthrough,
      advanceToStep,
      resetWalkthrough,
      reportWalkthroughError,
    };
  };
