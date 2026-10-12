import { isEmpty } from 'lodash';
import { useCallback, useState } from 'react';

import UseManageGuideQuestFormDefinition from './definitions/use-manage-guide-quest-form-definition';
import UseManageGuideQuestFormParams from './definitions/use-manage-guide-quest-form-params';
import GuideQuestDefinition, {
  GuideQuestWalkthroughStepDefinition,
} from '../api/definitions/guide-quest-definition';
import { useStoreGuideQuestContent } from '../api/hooks/use-store-guide-quest-content';
import { useStoreGuideQuestWalkthrough } from '../api/hooks/use-store-guide-quest-walkthrough';
import { makeRequestObject } from '../utils/guide-quest-form-data-util';

const LAST_STEP_INDEX = 13;
const FIRST_OPTIONAL_STEP_INDEX = 5;

export const useManageGuideQuestForm = ({
  guide_quest_id: guideQuestId,
  data,
  updateGuideQuest,
}: UseManageGuideQuestFormParams): UseManageGuideQuestFormDefinition => {
  const [formData, setFormData] = useState<
    Array<Partial<GuideQuestDefinition> | null>
  >([]);
  const {
    loading: isContentSaving,
    error: contentError,
    storeGuideQuestContent,
  } = useStoreGuideQuestContent({ update_guide_quest: updateGuideQuest });
  const {
    error: walkthroughError,
    loading: isWalkthroughSaving,
    storeWalkthrough,
  } = useStoreGuideQuestWalkthrough();

  const setStepData = useCallback(
    (step: number, stepData: Partial<GuideQuestDefinition>): void => {
      setFormData((previous) => {
        const next = previous.slice();

        if (next.length <= step) {
          next.length = step + 1;
        }

        next[step] = stepData;

        return next;
      });
    },
    []
  );

  const setWalkthroughSteps = useCallback(
    (steps: GuideQuestWalkthroughStepDefinition[]): void => {
      setStepData(4, { walkthrough_steps: steps });
    },
    [setStepData]
  );

  const handleNextStep = async (currentIndex: number): Promise<boolean> => {
    const stepData = formData[currentIndex];
    const isStepEmpty = !stepData || isEmpty(stepData);

    if (
      currentIndex >= FIRST_OPTIONAL_STEP_INDEX &&
      currentIndex < LAST_STEP_INDEX &&
      isStepEmpty
    ) {
      return true;
    }

    const currentQuestId = data?.guide_quest?.id ?? guideQuestId;

    if (currentIndex === 4) {
      if (currentQuestId <= 0) {
        return false;
      }

      return storeWalkthrough(
        currentQuestId,
        stepData?.walkthrough_steps ?? []
      );
    }

    const saved = await storeGuideQuestContent(
      makeRequestObject(currentQuestId, stepData ?? {})
    );

    if (saved && currentIndex === LAST_STEP_INDEX) {
      window.location.assign(`/admin/guide-quests/show/${currentQuestId}`);
    }

    return saved;
  };

  return {
    handleNextStep,
    setStepData,
    setWalkthroughSteps,
    isSaving: isContentSaving || isWalkthroughSaving,
    formError:
      contentError ??
      (walkthroughError === null ? null : { message: walkthroughError }),
  };
};
