import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import UnitBasicCombatFields from './unit-basic-combat-fields';
import UnitResourceCostFields from './unit-resource-cost-fields';
import AdminBackButton from '../../../../shared/components/admin-back-button';
import AdminPage from '../../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../../shared/enums/admin-page-width';
import { useFocusFirstInvalidUnitField } from '../../hooks/use-focus-first-invalid-unit-field';
import { useUnitForm } from '../../hooks/use-unit-form';
import UnitFormContentProps from '../../types/unit-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LAST_STEP_INDEX = 1;

const resolveFinishLabel = (saving: boolean, unitId: number | null): string => {
  if (saving) {
    return 'Saving…';
  }

  return unitId === null ? 'Create Unit' : 'Save Unit';
};

const UnitFormContent = ({
  unit_id: unitId,
  on_saved: onSaved,
  on_cancel: onCancel,
}: UnitFormContentProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    submit,
    validate_step: validateStep,
  } = useUnitForm(unitId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidUnitField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle = unitId === null ? 'Create Unit' : 'Edit Unit';

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

    if (loadError) {
      return <ApiErrorAlert apiError={loadError.message} />;
    }

    return (
      <>
        {renderSaveError()}

        <FormWizard
          total_steps={2}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={resolveFinishLabel(saving, unitId)}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Basic & Combat">
            <UnitBasicCombatFields
              state={formState}
              errors={fieldErrors}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Resource Costs">
            <UnitResourceCostFields
              state={formState}
              errors={fieldErrors}
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

export default UnitFormContent;
