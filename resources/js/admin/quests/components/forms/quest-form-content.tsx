import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import QuestRequirementsFields from './quest-requirements-fields';
import QuestRewardsFields from './quest-rewards-fields';
import QuestStoryFields from './quest-story-fields';
import QuestStructureFields from './quest-structure-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { QuestApiMessages } from '../../api/enums/quest-api-messages';
import { useFocusFirstInvalidQuestField } from '../../hooks/use-focus-first-invalid-quest-field';
import { useQuestForm } from '../../hooks/use-quest-form';
import QuestFormContentProps from '../../types/quest-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestFormContent = ({
  quest_id: questId,
  parent_quest_id: parentQuestId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: QuestFormContentProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    submit,
    validate_step: validateStep,
  } = useQuestForm(questId, parentQuestId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const { record_attempt: recordAttempt } = useFocusFirstInvalidQuestField(
    fieldErrors,
    currentStepIndex
  );

  const pageTitle = questId === null ? 'Create Quest' : 'Edit Quest';
  const finishLabel = saving
    ? 'Saving…'
    : questId === null
      ? 'Create Quest'
      : 'Save Quest';

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordAttempt();

      return false;
    }

    if (stepIndex === 3) {
      const saved = await submit();

      if (!saved) {
        return false;
      }

      onSaved(saved);

      return true;
    }

    return true;
  };

  const renderWizard = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError || !formOptions) {
      return (
        <ApiErrorAlert
          apiError={loadError?.message ?? QuestApiMessages.LoadFormOptions}
        />
      );
    }

    return (
      <>
        {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

        <FormWizard
          total_steps={4}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={finishLabel}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={setCurrentStepIndex}
        >
          <Step step_title="Quest & Story">
            <QuestStoryFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Structure & Dependencies">
            <QuestStructureFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Requirements">
            <QuestRequirementsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Rewards">
            <QuestRewardsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
        </FormWizard>
      </>
    );
  };

  if (embedded) {
    return <div>{renderWizard()}</div>;
  }

  return (
    <AdminPage
      title={pageTitle}
      width={AdminPageWidth.Standard}
      header_actions={<AdminBackButton on_click={onCancel} />}
    >
      {renderWizard()}
    </AdminPage>
  );
};

export default QuestFormContent;
