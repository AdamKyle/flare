import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import BuildingBasicFields from './building-basic-fields';
import BuildingUnitRecruitmentFields from './building-unit-recruitment-fields';
import BuildingUpgradeCostFields from './building-upgrade-cost-fields';
import BuildingUpgradeEffectFields from './building-upgrade-effect-fields';
import AdminBackButton from '../../../../shared/components/admin-back-button';
import AdminPage from '../../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../../shared/enums/admin-page-width';
import { BuildingApiMessages } from '../../api/enums/building-api-messages';
import { useBuildingForm } from '../../hooks/use-building-form';
import { useFocusFirstInvalidBuildingField } from '../../hooks/use-focus-first-invalid-building-field';
import BuildingFormContentProps from '../../types/building-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LAST_STEP_INDEX = 3;

const resolveFinishLabel = (
  saving: boolean,
  buildingId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  return buildingId === null ? 'Create Building' : 'Save Building';
};

const BuildingFormContent = ({
  building_id: buildingId,
  on_saved: onSaved,
  on_cancel: onCancel,
}: BuildingFormContentProps): ReactNode => {
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
  } = useBuildingForm(buildingId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidBuildingField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle = buildingId === null ? 'Create Building' : 'Edit Building';

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
          apiError={loadError?.message ?? BuildingApiMessages.LoadFormOptions}
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
          finish_label={resolveFinishLabel(saving, buildingId)}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Basic">
            <BuildingBasicFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Upgrade Costs">
            <BuildingUpgradeCostFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Upgrade Effects">
            <BuildingUpgradeEffectFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Unit Recruitment">
            <BuildingUnitRecruitmentFields
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

export default BuildingFormContent;
