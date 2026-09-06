import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import MapGemCurrencyDropsFields from './map-gem-currency-drops-fields';
import MapGemEnemyCombatFields from './map-gem-enemy-combat-fields';
import MapGemIdentityFields from './map-gem-identity-fields';
import MapGemMonsterRewardsFields from './map-gem-monster-rewards-fields';
import MapGemPlayerRewardsFields from './map-gem-player-rewards-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { MapGemApiMessages } from '../../api/enums/map-gem-api-messages';
import { useFocusFirstInvalidMapGemField } from '../../hooks/use-focus-first-invalid-map-gem-field';
import { useMapGemForm } from '../../hooks/use-map-gem-form';
import MapGemFormContentProps from '../../types/map-gem-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveFinishLabel = (saving: boolean, gemId: number | null): string => {
  if (saving) {
    return 'Saving…';
  }
  return gemId === null ? 'Create Map Gem' : 'Save Map Gem';
};

const MapGemFormContent = ({
  map_gem_id: mapGemId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: MapGemFormContentProps): ReactNode => {
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
  } = useMapGemForm(mapGemId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const recordInvalidAttempt = useFocusFirstInvalidMapGemField(
    fieldErrors,
    currentStepIndex,
    setCurrentStepIndex
  );

  const pageTitle = mapGemId === null ? 'Create Map Gem' : 'Edit Map Gem';
  const finishLabel = resolveFinishLabel(saving, mapGemId);

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
          apiError={loadError?.message ?? MapGemApiMessages.LoadFormOptions}
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
            <MapGemIdentityFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Player Rewards">
            <MapGemPlayerRewardsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Currency and Drops">
            <MapGemCurrencyDropsFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Enemy Combat">
            <MapGemEnemyCombatFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Monster Rewards">
            <MapGemMonsterRewardsFields
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

export default MapGemFormContent;
