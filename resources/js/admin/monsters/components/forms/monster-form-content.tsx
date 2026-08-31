import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import MonsterCombatFields from './monster-combat-fields';
import MonsterIdentityFields from './monster-identity-fields';
import MonsterQuestCelestialFields from './monster-quest-celestial-fields';
import MonsterRaidFields from './monster-raid-fields';
import MonsterSpellFields from './monster-spell-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { MonsterApiMessages } from '../../api/enums/monster-api-messages';
import { useFocusFirstInvalidMonsterField } from '../../hooks/use-focus-first-invalid-monster-field';
import { useMonsterForm } from '../../hooks/use-monster-form';
import MonsterFormContentProps from '../../types/monster-form-content-props';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MonsterFormContent = ({
  monster_id: monsterId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: MonsterFormContentProps): ReactNode => {
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
  } = useMonsterForm(monsterId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const { record_attempt: recordAttempt } = useFocusFirstInvalidMonsterField(
    fieldErrors,
    currentStepIndex
  );

  const pageTitle = monsterId === null ? 'Create Monster' : 'Edit Monster';
  const finishLabel = saving
    ? 'Saving…'
    : monsterId === null
      ? 'Create Monster'
      : 'Save Monster';

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (!validateStep(stepIndex)) {
      recordAttempt();

      return false;
    }

    if (stepIndex === 4) {
      const saved = await submit();

      if (!saved) {
        return false;
      }

      onSaved(saved);

      return true;
    }

    return true;
  };

  const renderWizard = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (loadError || !formOptions) {
      return (
        <ApiErrorAlert
          apiError={loadError?.message ?? MonsterApiMessages.LoadFormOptions}
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
          form_error={null}
          embedded
          current_step_index={currentStepIndex}
          on_step_change={setCurrentStepIndex}
        >
          <Step step_title="Identity & Placement">
            <MonsterIdentityFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Core Combat">
            <MonsterCombatFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Spells & Affixes">
            <MonsterSpellFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Quest & Celestial">
            <MonsterQuestCelestialFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Raid & Special Rules">
            <MonsterRaidFields
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

export default MonsterFormContent;
