import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useState } from 'react';

import ManageGuideQuestsTextContent from './manage-guide-quest-text-content';
import ManageGuideQuestsFormProps from './types/manage-guide-quests-form-props';
import GuideQuestResponseDefinition from '../api/definitions/guide-quest-response-definition';
import { useFetchGuideQuest } from '../api/hooks/use-fetch-guide-quest';
import ManageGuideQuestRequiredFaction from '../components/manage-guide-quest-required-faction';
import ManageGuideQuestWalkthrough from '../components/manage-guide-quest-walkthrough';
import ManageGuideQuestsBasicQuestAttributes from '../components/manage-guide-quests-basic-quest-attributes';
import ManageGuideQuestsRequiredClassRanksAttributes from '../components/manage-guide-quests-required-class-ranks-attributes';
import ManageGuideQuestsRequiredCurrencies from '../components/manage-guide-quests-required-currencies';
import ManageGuideQuestsRequiredItemAttributes from '../components/manage-guide-quests-required-item-attributes';
import ManageGuideQuestsRequiredKingdomAttributes from '../components/manage-guide-quests-required-kingdom-attributes';
import ManageGuideQuestsRequiredLevels from '../components/manage-guide-quests-required-levels';
import ManageGuideQuestsRequiredQuestAndPlaneAttributes from '../components/manage-guide-quests-required-quest-and-plane-attributes';
import ManageGuideQuestsRequiredStats from '../components/manage-guide-quests-required-stats';
import ManageGuideQuestsRewardsAndBonuses from '../components/manage-guide-quests-rewards-and-bonuses';
import { useFocusFirstInvalidGuideQuestField } from '../hooks/use-focus-first-invalid-guide-quest-field';
import { useManageGuideQuestForm } from '../hooks/use-manage-guide-quest-form';
import { firstGuideQuestErrorStep } from '../utils/guide-quest-form-validation';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import WideContainerWrapper from 'ui/container/wide-container-wrapper';
import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

interface LoadedGuideQuestFormProps {
  guideQuestId: number;
  data: GuideQuestResponseDefinition;
  updateGuideQuest: (data: GuideQuestResponseDefinition) => void;
}

const LoadedGuideQuestForm = ({
  guideQuestId,
  data,
  updateGuideQuest,
}: LoadedGuideQuestFormProps): ReactNode => {
  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const guideQuestForm = useManageGuideQuestForm({
    guide_quest_id: guideQuestId,
    data,
    updateGuideQuest,
  });
  const { record_attempt: recordAttempt } = useFocusFirstInvalidGuideQuestField(
    guideQuestForm.field_errors,
    currentStepIndex
  );
  const finishLabel =
    guideQuestId > 0 ? 'Save Guide Quest' : 'Create Guide Quest';

  useEffect(() => {
    if (!guideQuestForm.save_error) {
      return;
    }

    const errorStep = firstGuideQuestErrorStep(guideQuestForm.field_errors);

    if (errorStep === null) {
      return;
    }

    setCurrentStepIndex(errorStep);
    recordAttempt();
  }, [guideQuestForm.field_errors, guideQuestForm.save_error, recordAttempt]);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!guideQuestForm.validate_step(stepIndex)) {
      recordAttempt();
      return false;
    }

    if (stepIndex !== 13) {
      return true;
    }

    const saved = await guideQuestForm.submit();

    if (!saved) {
      return false;
    }

    window.location.assign(`/admin/guide-quests/show/${saved.id}`);
    return true;
  };

  const sharedProps = {
    state: guideQuestForm.form_state,
    errors: guideQuestForm.field_errors,
    form_options: data,
    on_change: guideQuestForm.update_field,
  };

  return (
    <>
      {guideQuestForm.save_error && (
        <ApiErrorAlert apiError={guideQuestForm.save_error.message} closable />
      )}
      <FormWizard
        total_steps={14}
        name="Create / Edit Guide Quest"
        is_loading={guideQuestForm.is_saving}
        on_request_next={handleRequestNext}
        form_error={null}
        finish_label={finishLabel}
        allow_direct_step_navigation={false}
        current_step_index={currentStepIndex}
        on_step_change={setCurrentStepIndex}
      >
        <Step step_title="Basic Info">
          <ManageGuideQuestsBasicQuestAttributes {...sharedProps} />
        </Step>
        <Step step_title="Introduction">
          <ManageGuideQuestsTextContent
            field_key="intro_text"
            value={guideQuestForm.form_state.intro_text}
            on_change={guideQuestForm.update_field}
          />
        </Step>
        <Step step_title="Desktop Instructions">
          <ManageGuideQuestsTextContent
            field_key="desktop_instructions"
            value={guideQuestForm.form_state.desktop_instructions}
            on_change={guideQuestForm.update_field}
          />
        </Step>
        <Step step_title="Mobile Instructions">
          <ManageGuideQuestsTextContent
            field_key="mobile_instructions"
            value={guideQuestForm.form_state.mobile_instructions}
            on_change={guideQuestForm.update_field}
          />
        </Step>
        <Step step_title="Guided Walkthrough">
          <ManageGuideQuestWalkthrough
            steps={guideQuestForm.form_state.walkthrough_steps}
            form_options={data}
            on_change={guideQuestForm.update_walkthrough_steps}
          />
        </Step>
        <Step step_title="Required levels">
          <ManageGuideQuestsRequiredLevels {...sharedProps} />
        </Step>
        <Step step_title="Faction Requirements">
          <ManageGuideQuestRequiredFaction {...sharedProps} />
        </Step>
        <Step step_title="Required Stats">
          <ManageGuideQuestsRequiredStats {...sharedProps} />
        </Step>
        <Step step_title="Required Kingdom Attributes">
          <ManageGuideQuestsRequiredKingdomAttributes {...sharedProps} />
        </Step>
        <Step step_title="Required Item Attributes">
          <ManageGuideQuestsRequiredItemAttributes {...sharedProps} />
        </Step>
        <Step step_title="Quest and Plane Requirements">
          <ManageGuideQuestsRequiredQuestAndPlaneAttributes {...sharedProps} />
        </Step>
        <Step step_title="Class Rank Requirements">
          <ManageGuideQuestsRequiredClassRanksAttributes {...sharedProps} />
        </Step>
        <Step step_title="Required Currencies">
          <ManageGuideQuestsRequiredCurrencies {...sharedProps} />
        </Step>
        <Step step_title="Rewards and Bonuses">
          <ManageGuideQuestsRewardsAndBonuses {...sharedProps} />
        </Step>
      </FormWizard>
    </>
  );
};

const ManageGuideQuestsForm = ({
  guide_quest_id: guideQuestId,
}: ManageGuideQuestsFormProps): ReactNode => {
  const { data, loading, error, updateGuideQuest } = useFetchGuideQuest({
    id: guideQuestId,
  });

  if (loading) {
    return (
      <WideContainerWrapper>
        <Card>
          <InfiniteLoader />
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

  if (!data) {
    return (
      <WideContainerWrapper>
        <Card>
          <Alert variant={AlertVariant.DANGER}>
            There seems to be no data for this request.
          </Alert>
        </Card>
      </WideContainerWrapper>
    );
  }

  return (
    <WideContainerWrapper>
      <LoadedGuideQuestForm
        guideQuestId={guideQuestId}
        data={data}
        updateGuideQuest={updateGuideQuest}
      />
    </WideContainerWrapper>
  );
};

export default ManageGuideQuestsForm;
