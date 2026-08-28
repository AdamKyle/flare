import React, { ReactNode, useState } from 'react';

import GameMapAccessFields from './game-map-access-fields';
import GameMapBasicFields from './game-map-basic-fields';
import GameMapBonusFields from './game-map-bonus-fields';
import { GameMapApiMessages } from '../../api/enums/game-map-api-messages';
import { useGameMapForm } from '../../hooks/use-game-map-form';
import GameMapFormContentProps from '../../types/game-map-form-content-props';
import { resolveGameMapFormFinishLabel } from '../../utils/resolve-game-map-form-finish-label';

import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';

import ApiErrorAlert from 'api-handler/components/api-error-alert';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapFormContent = ({
  game_map_id: gameMapId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: GameMapFormContentProps): ReactNode => {
  const {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    current_map_url: currentMapUrl,
    loading,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: fieldErrors,
    request_next: requestNext,
    submit,
  } = useGameMapForm(gameMapId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);

  const finishLabel = resolveGameMapFormFinishLabel(saving, gameMapId);
  const pageTitle = gameMapId === null ? 'Create Game Map' : 'Edit Game Map';

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (stepIndex === 2) {
      const isValid = requestNext(2);

      if (!isValid) {
        return false;
      }

      const saved = await submit();

      if (!saved) {
        return false;
      }

      onSaved(saved);

      return true;
    }

    return requestNext(stepIndex);
  };

  const renderWizard = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError || !formOptions) {
      return (
        <ApiErrorAlert
          apiError={loadError?.message ?? GameMapApiMessages.LoadFormOptions}
        />
      );
    }

    return (
      <>
        {saveError && <ApiErrorAlert apiError={saveError.message} closable />}

        <FormWizard
          total_steps={3}
          is_loading={saving}
          on_request_next={handleRequestNext}
          finish_label={finishLabel}
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={setCurrentStepIndex}
        >
          <Step step_title="Map" show_title={false}>
            <GameMapBasicFields
              state={formState}
              errors={fieldErrors}
              current_map_url={currentMapUrl}
              game_map_id={gameMapId}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Bonuses" show_title={false}>
            <GameMapBonusFields
              state={formState}
              errors={fieldErrors}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Access" show_title={false}>
            <GameMapAccessFields
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

export default GameMapFormContent;
