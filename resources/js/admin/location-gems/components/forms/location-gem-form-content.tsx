import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import LocationGemCurrencyDropsFields from './location-gem-currency-drops-fields';
import LocationGemEnemyCombatFields from './location-gem-enemy-combat-fields';
import LocationGemIdentityFields from './location-gem-identity-fields';
import LocationGemMonsterRewardsFields from './location-gem-monster-rewards-fields';
import LocationGemPlayerRewardsFields from './location-gem-player-rewards-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { LocationGemApiMessages } from '../../api/enums/location-gem-api-messages';
import { useFocusFirstInvalidLocationGemField } from '../../hooks/use-focus-first-invalid-location-gem-field';
import { useLocationGemForm } from '../../hooks/use-location-gem-form';
import LocationGemFormContentProps from '../../types/location-gem-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveFinishLabel = (saving: boolean, gemId: number | null): string => {
  if (saving) {
    return 'Saving…';
  }
  return gemId === null ? 'Create Location Gem' : 'Save Location Gem';
};

const LocationGemFormContent = ({
  location_gem_id: locationGemId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: LocationGemFormContentProps): ReactNode => {
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
  } = useLocationGemForm(locationGemId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidLocationGemField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle =
    locationGemId === null ? 'Create Location Gem' : 'Edit Location Gem';
  const finishLabel = resolveFinishLabel(saving, locationGemId);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordInvalidAttempt();
      return false;
    }

    if (stepIndex === 4) {
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
            loadError?.message ?? LocationGemApiMessages.LoadFormOptions
          }
        />
      );
    }

    return (
      <>
        {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

        <FormWizard
          total_steps={5}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={finishLabel}
          form_error={formError ? { message: formError } : null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={handleStepChange}
        >
          <Step step_title="Identity">
            <LocationGemIdentityFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Player Rewards">
            <LocationGemPlayerRewardsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Currency and Drops">
            <LocationGemCurrencyDropsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Enemy Combat">
            <LocationGemEnemyCombatFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Monster Rewards">
            <LocationGemMonsterRewardsFields
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

export default LocationGemFormContent;
