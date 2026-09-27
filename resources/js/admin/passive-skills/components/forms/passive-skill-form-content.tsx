import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import PassiveSkillBasicFields from './passive-skill-basic-fields';
import PassiveSkillBonusFields from './passive-skill-bonus-fields';
import PassiveSkillTreeFields from './passive-skill-tree-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { PassiveSkillApiMessages } from '../../api/enums/passive-skill-api-messages';
import { useFocusFirstInvalidPassiveSkillField } from '../../hooks/use-focus-first-invalid-passive-skill-field';
import { usePassiveSkillForm } from '../../hooks/use-passive-skill-form';
import PassiveSkillFormContentProps from '../../types/passive-skill-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LAST_STEP_INDEX = 2;

const resolveFinishLabel = (
  saving: boolean,
  passiveSkillId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  return passiveSkillId === null
    ? 'Create Passive Skill'
    : 'Save Passive Skill';
};

const PassiveSkillFormContent = ({
  passive_skill_id: passiveSkillId,
  on_saved: onSaved,
  on_cancel: onCancel,
}: PassiveSkillFormContentProps): ReactNode => {
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
  } = usePassiveSkillForm(passiveSkillId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidPassiveSkillField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle =
    passiveSkillId === null ? 'Create Passive Skill' : 'Edit Passive Skill';

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordInvalidAttempt();

      return false;
    }

    if (stepIndex !== LAST_STEP_INDEX) {
      return true;
    }

    const saved = await submit();

    if (!saved) {
      recordInvalidAttempt();

      return false;
    }

    onSaved(saved);

    return true;
  };

  const handleStepChange = (targetStepIndex: number): void => {
    if (targetStepIndex === currentStepIndex) {
      return;
    }

    if (targetStepIndex < currentStepIndex) {
      setCurrentStepIndex(targetStepIndex);

      return;
    }

    if (targetStepIndex > currentStepIndex + 1) {
      return;
    }

    if (!validateStep(currentStepIndex)) {
      recordInvalidAttempt();

      return;
    }

    setCurrentStepIndex(targetStepIndex);
  };

  const renderSaveError = (): ReactNode => {
    if (!saveError) {
      return null;
    }

    return <ApiErrorAlert apiError={saveError.message} closable />;
  };

  const renderWizard = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError || !formOptions) {
      return (
        <ApiErrorAlert
          apiError={
            loadError?.message ?? PassiveSkillApiMessages.LoadFormOptions
          }
        />
      );
    }

    return (
      <>
        {renderSaveError()}

        <FormWizard
          total_steps={3}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={resolveFinishLabel(saving, passiveSkillId)}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Basic">
            <PassiveSkillBasicFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              passive_skill_id={passiveSkillId}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Bonuses">
            <PassiveSkillBonusFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              passive_skill_id={passiveSkillId}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Tree / Unlock">
            <PassiveSkillTreeFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              passive_skill_id={passiveSkillId}
              on_change={updateField}
            />
          </Step>
        </FormWizard>
      </>
    );
  };

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

export default PassiveSkillFormContent;
