import React, { ReactNode, useState } from 'react';

import { NpcApiMessages } from '../api/enums/npc-api-messages';
import NpcBasicFields from '../components/forms/npc-basic-fields';
import NpcPositionFields from '../components/forms/npc-position-fields';
import { useNpcForm } from '../hooks/use-npc-form';
import NpcFormScreenProps from '../types/npc-form-screen-props';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const NpcFormScreen = ({
  game_map_id,
  npc_id,
  initial_x,
  initial_y,
  on_saved,
  on_cancel,
  embedded = false,
}: NpcFormScreenProps): ReactNode => {
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
  } = useNpcForm(game_map_id, npc_id, initial_x, initial_y, on_saved);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (stepIndex === 1) {
      const isValid = requestNext(1);

      if (!isValid) {
        return false;
      }

      return submit();
    }

    return requestNext(stepIndex);
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (loadError || !formOptions) {
    return (
      <ApiErrorAlert apiError={loadError?.message ?? NpcApiMessages.LoadForm} />
    );
  }

  const wizard = (
    <>
      {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

      <FormWizard
        total_steps={2}
        is_loading={saving}
        on_request_next={handleRequestNext}
        finish_label={saving ? 'Saving…' : 'Save Npc'}
        form_error={null}
        embedded
        current_step_index={currentStepIndex}
        on_step_change={setCurrentStepIndex}
      >
        <Step step_title="Details">
          <NpcBasicFields
            game_map_name={formOptions.game_map.name}
            state={formState}
            errors={fieldErrors}
            form_options={formOptions}
            on_change={updateField}
          />
        </Step>
        <Step step_title="Position">
          <NpcPositionFields
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
    <div className="container mx-auto my-4 px-4">
      <button
        type="button"
        onClick={on_cancel}
        className="text-danube-600 focus:ring-danube-500 dark:text-danube-300 mb-4 text-sm font-medium hover:underline focus:ring-2 focus:outline-none"
      >
        &larr; Back
      </button>

      <h1 className="text-glacier-900 dark:text-glacier-100 mb-4 text-xl font-semibold">
        {npc_id ? 'Edit Npc' : 'Create Npc'}
      </h1>

      {wizard}
    </div>
  );
};

export default NpcFormScreen;
