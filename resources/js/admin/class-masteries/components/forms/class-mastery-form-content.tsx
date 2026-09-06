import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import ClassMasteryAttackFields from './class-mastery-attack-fields';
import ClassMasteryEvasionReductionsFields from './class-mastery-evasion-reductions-fields';
import ClassMasteryIdentityFields from './class-mastery-identity-fields';
import ClassMasteryModifiersFields from './class-mastery-modifiers-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { ClassMasteryApiMessages } from '../../api/enums/class-mastery-api-messages';
import { useClassMasteryForm } from '../../hooks/use-class-mastery-form';
import { useFocusFirstInvalidClassMasteryField } from '../../hooks/use-focus-first-invalid-class-mastery-field';
import ClassMasteryFormContentProps from '../../types/class-mastery-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveFinishLabel = (
  saving: boolean,
  masteryId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }
  return masteryId === null ? 'Create Class Mastery' : 'Save Class Mastery';
};

const ClassMasteryFormContent = ({
  class_mastery_id: classMasteryId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: ClassMasteryFormContentProps): ReactNode => {
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
  } = useClassMasteryForm(classMasteryId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidClassMasteryField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle =
    classMasteryId === null ? 'Create Class Mastery' : 'Edit Class Mastery';
  const finishLabel = resolveFinishLabel(saving, classMasteryId);

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
          apiError={
            loadError?.message ?? ClassMasteryApiMessages.LoadFormOptions
          }
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
          <Step step_title="Identity">
            <ClassMasteryIdentityFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Attack">
            <ClassMasteryAttackFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Modifiers">
            <ClassMasteryModifiersFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Evasion / Reductions">
            <ClassMasteryEvasionReductionsFields
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

export default ClassMasteryFormContent;
