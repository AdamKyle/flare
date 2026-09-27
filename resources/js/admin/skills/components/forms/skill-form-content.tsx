import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import SkillBasicFields from './skill-basic-fields';
import SkillCharacterModifierFields from './skill-character-modifier-fields';
import SkillKingdomModifierFields from './skill-kingdom-modifier-fields';
import SkillTimerModifierFields from './skill-timer-modifier-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { SkillApiMessages } from '../../api/enums/skill-api-messages';
import { useFocusFirstInvalidSkillField } from '../../hooks/use-focus-first-invalid-skill-field';
import { useSkillForm } from '../../hooks/use-skill-form';
import SkillFormContentProps from '../../types/skill-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LAST_STEP_INDEX = 3;

const resolveFinishLabel = (
  saving: boolean,
  skillId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  return skillId === null ? 'Create Skill' : 'Save Skill';
};

const SkillFormContent = ({
  skill_id: skillId,
  on_saved: onSaved,
  on_cancel: onCancel,
}: SkillFormContentProps): ReactNode => {
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
  } = useSkillForm(skillId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidSkillField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle = skillId === null ? 'Create Skill' : 'Edit Skill';

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
          apiError={loadError?.message ?? SkillApiMessages.LoadFormOptions}
        />
      );
    }

    return (
      <>
        {renderSaveError()}

        <FormWizard
          total_steps={4}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={resolveFinishLabel(saving, skillId)}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Basic">
            <SkillBasicFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Character Modifiers">
            <SkillCharacterModifierFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Timers & Class">
            <SkillTimerModifierFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Kingdom Modifiers">
            <SkillKingdomModifierFields
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

export default SkillFormContent;
