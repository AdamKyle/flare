import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import ClassAttributesFields from './class-attributes-fields';
import ClassBasicFields from './class-basic-fields';
import ClassCombatFields from './class-combat-fields';
import ClassUnlockFields from './class-unlock-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { ClassApiMessages } from '../../api/enums/class-api-messages';
import { useClassForm } from '../../hooks/use-class-form';
import { useFocusFirstInvalidClassField } from '../../hooks/use-focus-first-invalid-class-field';
import ClassFormContentProps from '../../types/class-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveFinishLabel = (
  saving: boolean,
  classId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }
  return classId === null ? 'Create Class' : 'Save Class';
};

const ClassFormContent = ({
  class_id: classId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: ClassFormContentProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    form_error: formError,
    submit,
    validate_step: validateStep,
  } = useClassForm(classId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidClassField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle = classId === null ? 'Create Class' : 'Edit Class';
  const finishLabel = resolveFinishLabel(saving, classId);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordInvalidAttempt();
      return false;
    }

    if (stepIndex === 3) {
      const saved = await submit();

      if (!saved) {
        recordInvalidAttempt();

        return false;
      }

      onSaved(saved);

      return true;
    }

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

  const renderWizard = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError || !formOptions) {
      return (
        <ApiErrorAlert
          apiError={loadError?.message ?? ClassApiMessages.LoadFormOptions}
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
          form_error={formError ? { message: formError } : null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Basic">
            <ClassBasicFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Attributes">
            <ClassAttributesFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Combat Modifiers">
            <ClassCombatFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Unlock Requirements">
            <ClassUnlockFields
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

export default ClassFormContent;
