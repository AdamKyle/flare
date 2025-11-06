import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty, debounce } from 'lodash';
import React, { useMemo, useState } from 'react';

import ManageGuideQuestSectionContent from '../components/manage-guide-quest-section-content';
import ManageGuideQuestsFormProps from './types/manage-guide-quests-form-props';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';
import { useFetchGuideQuest } from '../api/hooks/use-fetch-guide-quest';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import WideContainerWrapper from 'ui/container/wide-container-wrapper';
import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ManageGuideQuestsForm = ({
  guide_quest_id,
}: ManageGuideQuestsFormProps) => {
  const { data, loading, error } = useFetchGuideQuest({ id: guide_quest_id });

  const [formData, setFormData] = useState<
    Array<Partial<GuideQuestDefinition> | null>
  >([]);
  const [name_value, set_name_value] = useState('');

  const debounced_store_name = useMemo(
    () =>
      debounce((value: string) => {
        setFormData((prev) => {
          const next = prev.slice();
          if (next.length === 0) {
            next.length = 1;
          }
          const existing = next[0] ?? {};
          next[0] = { ...existing, name: value };
          return next;
        });
      }, 300),
    []
  );

  const handleNextStep = (current_index: number): boolean => {
    const data_for_submission = formData[current_index];

    if (!data_for_submission || isEmpty(data_for_submission)) {
      return false;
    }

    console.log(current_index, data_for_submission);

    return true;
  };

  const handleNameChange = (value: string) => {
    set_name_value(value);
    debounced_store_name(value);
  };

  const handleIntroTextFormData = (
    step: number,
    data: Partial<GuideQuestDefinition>
  ) => {
    setFormData((prev) => {
      const next = prev.slice();

      if (next.length <= step) {
        next.length = step + 1;
      }

      next[step] = data;

      return next;
    });
  };

  if (loading && guide_quest_id !== 0) {
    return (
      <WideContainerWrapper>
        <Card>
          <InfiniteLoader />
        </Card>
      </WideContainerWrapper>
    );
  }

  if (!data && guide_quest_id !== 0) {
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
        total_steps={2}
        name="Create / Edit Guide Quest"
        is_loading={false}
        on_request_next={handleNextStep}
      >
        <Step step_title="Basic Info" key="basic">
          <Input
            on_change={handleNameChange}
            value={name_value}
            place_holder="Whats the name?"
          />
        </Step>

        <Step step_title="Introduction" key="intro">
          <ManageGuideQuestSectionContent
            step={1}
            on_update_content={handleIntroTextFormData}
          />
        </Step>

        <Step step_title={'Desktop Instructions'} key={'desktop-instructions'}>
          A new form
        </Step>
      </FormWizard>
    </WideContainerWrapper>
  );
};

export default ManageGuideQuestsForm;
