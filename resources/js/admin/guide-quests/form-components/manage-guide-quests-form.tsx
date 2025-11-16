import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty, isNil } from 'lodash';
import React, { useCallback, useEffect, useRef, useState } from 'react';

import ManageGuideQuestsTextContent from './manage-guide-quest-text-content';
import ManageGuideQuestsFormProps from './types/manage-guide-quests-form-props';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';
import { useFetchGuideQuest } from '../api/hooks/use-fetch-guide-quest';
import { useStoreGuideQuestContent } from '../api/hooks/use-store-guide-quest-content';
import ManageGuideQuestRequiredFaction from '../components/manage-guide-quest-required-faction';
import ManageGuideQuestsBasicQuestAttributes from '../components/manage-guide-quests-basic-quest-attributes';
import ManageGuideQuestsRequiredClassRanksAttributes from '../components/manage-guide-quests-required-class-ranks-attributes';
import ManageGuideQuestsRequiredCurrencies from '../components/manage-guide-quests-required-currencies';
import ManageGuideQuestsRequiredItemAttributes from '../components/manage-guide-quests-required-item-attributes';
import ManageGuideQuestsRequiredKingdomAttributes from '../components/manage-guide-quests-required-kingdom-attributes';
import ManageGuideQuestsRequiredLevels from '../components/manage-guide-quests-required-levels';
import ManageGuideQuestsRequiredQuestAndPlaneAttributes from '../components/manage-guide-quests-required-quest-and-plane-attributes';
import ManageGuideQuestsRequiredStats from '../components/manage-guide-quests-required-stats';
import ManageGuideQuestsRewardsAndBonuses from '../components/manage-guide-quests-rewards-and-bonuses';
import { makeRequestObject } from '../utils/guide-quest-form-data-util';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import WideContainerWrapper from 'ui/container/wide-container-wrapper';
import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ManageGuideQuestsForm = ({
  guide_quest_id,
}: ManageGuideQuestsFormProps) => {
  const { data, loading, error, updateGuideQuest } = useFetchGuideQuest({
    id: guide_quest_id,
  });

  const {
    loading: currentlyStoring,
    error: storageError,
    setRequestParams,
    canMoveForward,
  } = useStoreGuideQuestContent({ update_guide_quest: updateGuideQuest });

  const [formData, setFormData] = useState<
    Array<Partial<GuideQuestDefinition> | null>
  >([]);

  const storingRef = useRef(currentlyStoring);
  const canMoveForwardRef = useRef(canMoveForward);
  const storageErrorRef = useRef(storageError);

  useEffect(() => {
    storingRef.current = currentlyStoring;
    canMoveForwardRef.current = canMoveForward;
    storageErrorRef.current = storageError;
  }, [currentlyStoring, canMoveForward, storageError]);

  const handleNextStep = async (current_index: number): Promise<boolean> => {
    const lastStepIndex = 12;

    const data_for_submission = formData[current_index];
    const isFormDataEmpty =
      !data_for_submission || isEmpty(data_for_submission);

    if (
      current_index >= 4 &&
      current_index < lastStepIndex &&
      isFormDataEmpty
    ) {
      return true;
    }

    let questId = guide_quest_id;

    if (!isNil(data) && !isNil(data?.guide_quest)) {
      questId = data.guide_quest.id;
    }

    const requestObject = makeRequestObject(questId, data_for_submission ?? {});
    setRequestParams(requestObject);

    const canProceed =
      !isFormDataEmpty && !!canMoveForwardRef.current && isNil(error);

    if (canProceed && current_index === lastStepIndex) {
      window.location.assign(`/admin/guide-quests/show/${questId}`);
    }

    return canProceed;
  };

  const handleSetFormDataFromComponent = useCallback(
    (step: number, data: Partial<GuideQuestDefinition>) => {
      setFormData((prev) => {
        const next = prev.slice();

        if (next.length <= step) {
          next.length = step + 1;
        }

        next[step] = data;

        return next;
      });
    },
    []
  );

  const handleUpdateStep0 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(0, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep4 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(4, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep5 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(5, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep6 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(6, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep7 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(7, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep8 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(8, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep9 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(9, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep10 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(10, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep11 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(11, updated);
    },
    [handleSetFormDataFromComponent]
  );

  const handleUpdateStep12 = useCallback(
    (updated: Partial<GuideQuestDefinition>) => {
      handleSetFormDataFromComponent(12, updated);
    },
    [handleSetFormDataFromComponent]
  );

  if (loading) {
    return (
      <WideContainerWrapper>
        <Card>
          <InfiniteLoader />
        </Card>
      </WideContainerWrapper>
    );
  }

  if (!data) {
    return (
      <WideContainerWrapper>
        <Card>
          <Alert variant={AlertVariant.DANGER}>
            {' '}
            There seems to be no data for this request.{' '}
          </Alert>
        </Card>
      </WideContainerWrapper>
    );
  }

  if (error) {
    return (
      <WideContainerWrapper>
        <Card>
          <ApiErrorAlert apiError={error.message} />
        </Card>
      </WideContainerWrapper>
    );
  }

  return (
    <WideContainerWrapper>
      <FormWizard
        total_steps={13}
        name="Create / Edit Guide Quest"
        is_loading={currentlyStoring}
        on_request_next={handleNextStep}
        form_error={storageError}
      >
        <Step step_title="Basic Info" key="basic">
          <ManageGuideQuestsBasicQuestAttributes
            data_for_component={data}
            on_update={handleUpdateStep0}
          />
        </Step>

        <Step step_title="Introduction" key="intro">
          <ManageGuideQuestsTextContent
            step={1}
            on_update_content={handleSetFormDataFromComponent}
            field_key={'intro_text'}
            initial_content={data.guide_quest}
          />
        </Step>

        <Step step_title={'Desktop Instructions'} key={'desktop-instructions'}>
          <ManageGuideQuestsTextContent
            step={2}
            on_update_content={handleSetFormDataFromComponent}
            field_key={'desktop_instructions'}
            initial_content={data.guide_quest}
          />
        </Step>
        <Step step_title={'Mobile Instructions'} key={'mobile-instructions'}>
          <ManageGuideQuestsTextContent
            step={3}
            on_update_content={handleSetFormDataFromComponent}
            field_key={'mobile_instructions'}
            initial_content={data.guide_quest}
          />
        </Step>
        <Step step_title={'Required levels'} key={'required-levels'}>
          <ManageGuideQuestsRequiredLevels
            data_for_component={data}
            on_update={handleUpdateStep4}
          />
        </Step>
        <Step
          step_title={'Faction Requirements'}
          key={'required-faction-levels'}
        >
          <ManageGuideQuestRequiredFaction
            data_for_component={data}
            on_update={handleUpdateStep5}
          />
        </Step>
        <Step step_title={'Required Stats'} key={'required-stats'}>
          <ManageGuideQuestsRequiredStats
            data_for_component={data}
            on_update={handleUpdateStep6}
          />
        </Step>
        <Step
          step_title={'Required Kingdom Attributes'}
          key={'required-kingdom-attributes'}
        >
          <ManageGuideQuestsRequiredKingdomAttributes
            data_for_component={data}
            on_update={handleUpdateStep7}
          />
        </Step>

        <Step
          step_title={'Required Item Attributes'}
          key={'required-item-attributes'}
        >
          <ManageGuideQuestsRequiredItemAttributes
            data_for_component={data}
            on_update={handleUpdateStep8}
          />
        </Step>

        <Step
          step_title={'Quest and Plane Requirements'}
          key={'required-quest-attributes'}
        >
          <ManageGuideQuestsRequiredQuestAndPlaneAttributes
            data_for_component={data}
            on_update={handleUpdateStep9}
          />
        </Step>

        <Step
          step_title={'Class Rank Requirements'}
          key={'required-class-rank-attributes'}
        >
          <ManageGuideQuestsRequiredClassRanksAttributes
            data_for_component={data}
            on_update={handleUpdateStep10}
          />
        </Step>

        <Step step_title={'Required Currencies'} key={'required-currencies'}>
          <ManageGuideQuestsRequiredCurrencies
            data_for_component={data}
            on_update={handleUpdateStep11}
          />
        </Step>

        <Step step_title={'Rewards and Bonuses'} key={'rewards-and-bonuses'}>
          <ManageGuideQuestsRewardsAndBonuses
            data_for_component={data}
            on_update={handleUpdateStep12}
          />
        </Step>
      </FormWizard>
    </WideContainerWrapper>
  );
};

export default ManageGuideQuestsForm;
