import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { LocationApiMessages } from '../api/enums/location-api-messages';
import LocationBasicFields from '../components/location-basic-fields';
import LocationRulesFields from '../components/location-rules-fields';
import { useFocusFirstInvalidLocationField } from '../hooks/use-focus-first-invalid-location-field';
import { useLocationForm } from '../hooks/use-location-form';
import LocationFormScreenProps from '../types/location-form-screen-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationFormScreen = ({
  game_map_id,
  location_id,
  initial_x,
  initial_y,
  on_saved,
  on_cancel,
  embedded = false,
}: LocationFormScreenProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    request_next: requestNext,
    submit,
  } = useLocationForm(game_map_id, location_id, initial_x, initial_y, on_saved);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const { record_attempt: recordAttempt } = useFocusFirstInvalidLocationField(
    fieldErrors,
    setCurrentStepIndex
  );

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (stepIndex === 1) {
      const isValid = requestNext(1);

      if (!isValid) {
        recordAttempt();

        return false;
      }

      const saved = await submit();

      if (saved) {
        return true;
      }

      recordAttempt();

      return false;
    }

    const isValid = requestNext(stepIndex);

    recordAttempt();

    return isValid;
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (loadError || !formOptions) {
    return (
      <ApiErrorAlert
        apiError={loadError?.message ?? LocationApiMessages.LoadForm}
      />
    );
  }

  const wizard = (
    <>
      {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

      <FormWizard
        total_steps={2}
        is_loading={saving}
        on_request_next={handleRequestNext}
        finish_label={saving ? 'Saving…' : 'Save Location'}
        form_error={null}
        embedded
        current_step_index={currentStepIndex}
        on_step_change={setCurrentStepIndex}
      >
        <Step step_title="Basic Information">
          <LocationBasicFields
            game_map_name={formOptions.game_map.name}
            state={formState}
            errors={fieldErrors}
            coordinates={formOptions.coordinates}
            on_change={updateField}
          />
        </Step>
        <Step step_title="Rules">
          <LocationRulesFields
            state={formState}
            errors={fieldErrors}
            form_options={formOptions}
            on_change={updateField}
          />
        </Step>
      </FormWizard>
    </>
  );

  if (embedded) {
    return wizard;
  }

  return (
    <AdminPage
      title={location_id ? 'Edit Location' : 'Create Location'}
      width={AdminPageWidth.Standard}
      header_actions={<AdminBackButton on_click={on_cancel} />}
    >
      {wizard}
    </AdminPage>
  );
};

export default LocationFormScreen;
