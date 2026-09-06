import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import RaceFields from './race-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { useFocusFirstInvalidRaceField } from '../../hooks/use-focus-first-invalid-race-field';
import { useRaceForm } from '../../hooks/use-race-form';
import RaceFormContentProps from '../../types/race-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const resolveFinishLabel = (saving: boolean, raceId: number | null): string => {
  if (saving) {
    return 'Saving…';
  }
  return raceId === null ? 'Create Race' : 'Save Race';
};

const RaceFormContent = ({
  race_id: raceId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: RaceFormContentProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    form_error: formError,
    submit,
    validate_step: validateStep,
  } = useRaceForm(raceId);
  const recordInvalidAttempt = useFocusFirstInvalidRaceField(fieldErrors);

  const pageTitle = raceId === null ? 'Create Race' : 'Edit Race';
  const finishLabel = resolveFinishLabel(saving, raceId);

  const handleRequestNext = async (): Promise<boolean> => {
    if (!validateStep()) {
      recordInvalidAttempt();
      return false;
    }

    const saved = await submit();

    if (!saved) {
      recordInvalidAttempt();

      return false;
    }

    onSaved(saved);

    return true;
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
        {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

        <FormWizard
          total_steps={1}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={finishLabel}
          form_error={formError ? { message: formError } : null}
          embedded
        >
          <Step step_title="Race Details">
            <RaceFields
              state={formState}
              errors={fieldErrors}
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

export default RaceFormContent;
