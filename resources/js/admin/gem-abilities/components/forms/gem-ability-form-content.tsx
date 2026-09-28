import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import GemAbilityEffectFields from './gem-ability-effect-fields';
import GemAbilityIdentityFields from './gem-ability-identity-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { GemAbilityApiMessages } from '../../api/enums/gem-ability-api-messages';
import { useFocusFirstInvalidGemAbilityField } from '../../hooks/use-focus-first-invalid-gem-ability-field';
import { useGemAbilityForm } from '../../hooks/use-gem-ability-form';
import GemAbilityFormContentProps from '../../types/gem-ability-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const FINAL_STEP_INDEX = 1;

const resolveFinishLabel = (
  saving: boolean,
  gemAbilityId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  return gemAbilityId === null ? 'Create Gem Ability' : 'Save Gem Ability';
};

const GemAbilityFormContent = ({
  gem_ability_id: gemAbilityId,
  on_saved: onSaved,
  on_cancel: onCancel,
}: GemAbilityFormContentProps): ReactNode => {
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
  } = useGemAbilityForm(gemAbilityId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidGemAbilityField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle =
    gemAbilityId === null ? 'Create Gem Ability' : 'Edit Gem Ability';
  const finishLabel = resolveFinishLabel(saving, gemAbilityId);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordInvalidAttempt();

      return false;
    }

    if (stepIndex !== FINAL_STEP_INDEX) {
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
          apiError={loadError?.message ?? GemAbilityApiMessages.LoadFormOptions}
        />
      );
    }

    return (
      <>
        {renderSaveError()}

        <FormWizard
          total_steps={2}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={finishLabel}
          form_error={formError ? { message: formError } : null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Identity">
            <GemAbilityIdentityFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Effect">
            <GemAbilityEffectFields
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

export default GemAbilityFormContent;
