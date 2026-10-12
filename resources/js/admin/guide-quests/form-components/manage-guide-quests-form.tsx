import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React from 'react';

import ManageGuideQuestsTextContent from './manage-guide-quest-text-content';
import ManageGuideQuestsFormProps from './types/manage-guide-quests-form-props';
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
import { useManageGuideQuestForm } from '../hooks/use-manage-guide-quest-form';

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

  const guideQuestForm = useManageGuideQuestForm({
    guide_quest_id,
    data,
    updateGuideQuest,
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
        total_steps={14}
        name="Create / Edit Guide Quest"
        is_loading={guideQuestForm.isSaving}
        on_request_next={guideQuestForm.handleNextStep}
        form_error={guideQuestForm.formError}
        allow_direct_step_navigation={false}
      >
        <Step step_title="Basic Info" key="basic">
          <ManageGuideQuestsBasicQuestAttributes
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(0, updated)}
          />
        </Step>

        <Step step_title="Introduction" key="intro">
          <ManageGuideQuestsTextContent
            step={1}
            on_update_content={guideQuestForm.setStepData}
            field_key={'intro_text'}
            initial_content={data.guide_quest}
          />
        </Step>

        <Step step_title={'Desktop Instructions'} key={'desktop-instructions'}>
          <ManageGuideQuestsTextContent
            step={2}
            on_update_content={guideQuestForm.setStepData}
            field_key={'desktop_instructions'}
            initial_content={data.guide_quest}
          />
        </Step>
        <Step step_title={'Mobile Instructions'} key={'mobile-instructions'}>
          <ManageGuideQuestsTextContent
            step={3}
            on_update_content={guideQuestForm.setStepData}
            field_key={'mobile_instructions'}
            initial_content={data.guide_quest}
          />
        </Step>
        <Step step_title="Guided Walkthrough" key="guided-walkthrough">
          <ManageGuideQuestWalkthrough
            data={data}
            on_update={guideQuestForm.setWalkthroughSteps}
          />
        </Step>
        <Step step_title={'Required levels'} key={'required-levels'}>
          <ManageGuideQuestsRequiredLevels
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(5, updated)}
          />
        </Step>
        <Step
          step_title={'Faction Requirements'}
          key={'required-faction-levels'}
        >
          <ManageGuideQuestRequiredFaction
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(6, updated)}
          />
        </Step>
        <Step step_title={'Required Stats'} key={'required-stats'}>
          <ManageGuideQuestsRequiredStats
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(7, updated)}
          />
        </Step>
        <Step
          step_title={'Required Kingdom Attributes'}
          key={'required-kingdom-attributes'}
        >
          <ManageGuideQuestsRequiredKingdomAttributes
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(8, updated)}
          />
        </Step>

        <Step
          step_title={'Required Item Attributes'}
          key={'required-item-attributes'}
        >
          <ManageGuideQuestsRequiredItemAttributes
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(9, updated)}
          />
        </Step>

        <Step
          step_title={'Quest and Plane Requirements'}
          key={'required-quest-attributes'}
        >
          <ManageGuideQuestsRequiredQuestAndPlaneAttributes
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(10, updated)}
          />
        </Step>

        <Step
          step_title={'Class Rank Requirements'}
          key={'required-class-rank-attributes'}
        >
          <ManageGuideQuestsRequiredClassRanksAttributes
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(11, updated)}
          />
        </Step>

        <Step step_title={'Required Currencies'} key={'required-currencies'}>
          <ManageGuideQuestsRequiredCurrencies
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(12, updated)}
          />
        </Step>

        <Step step_title={'Rewards and Bonuses'} key={'rewards-and-bonuses'}>
          <ManageGuideQuestsRewardsAndBonuses
            data_for_component={data}
            on_update={(updated) => guideQuestForm.setStepData(13, updated)}
          />
        </Step>
      </FormWizard>
    </WideContainerWrapper>
  );
};

export default ManageGuideQuestsForm;
