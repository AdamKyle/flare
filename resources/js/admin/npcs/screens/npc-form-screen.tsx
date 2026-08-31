import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { NpcApiMessages } from '../api/enums/npc-api-messages';
import NpcBasicFields from '../components/forms/npc-basic-fields';
import NpcPositionFields from '../components/forms/npc-position-fields';
import { useNpcForm } from '../hooks/use-npc-form';
import NpcFormScreenProps from '../types/npc-form-screen-props';

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
        finish_label={saving ? 'Saving…' : 'Save NPC'}
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
    <AdminPage
      title={npc_id ? 'Edit NPC' : 'Create NPC'}
      width={AdminPageWidth.Standard}
      header_actions={<AdminBackButton on_click={on_cancel} />}
    >
      {wizard}
    </AdminPage>
  );
};

export default NpcFormScreen;
