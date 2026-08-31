import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import ItemBasicFields from './item-basic-fields';
import ItemCombatFields from './item-combat-fields';
import ItemCraftingFields from './item-crafting-fields';
import ItemQuestEffectFields from './item-quest-effect-fields';
import ItemUsableFields from './item-usable-fields';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { ItemApiMessages } from '../../api/enums/item-api-messages';
import { useItemForm } from '../../hooks/use-item-form';
import ItemFormContentProps from '../../types/item-form-content-props';
import { resolveItemFormFinishLabel } from '../../utils/resolve-item-form-finish-label';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ItemFormContent = ({
  item_id: itemId,
  on_saved: onSaved,
  on_cancel: onCancel,
  embedded,
}: ItemFormContentProps): ReactNode => {
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
  } = useItemForm(itemId);

  const [currentStepIndex, setCurrentStepIndex] = useState(0);

  const pageTitle = itemId === null ? 'Create Item' : 'Edit Item';
  const finishLabel = resolveItemFormFinishLabel(saving, itemId);

  const handleRequestNext = async (stepIndex: number): Promise<boolean> => {
    if (stepIndex === 5) {
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
          apiError={loadError?.message ?? ItemApiMessages.LoadFormOptions}
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
          <Step step_title="Basic">
            <ItemBasicFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Combat">
            <ItemCombatFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Quest & Effects">
            <ItemQuestEffectFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Crafting">
            <ItemCraftingFields
              state={formState}
              errors={fieldErrors}
              form_options={formOptions}
              on_change={updateField}
            />
          </Step>
          <Step step_title="Usable & Alchemy">
            <ItemUsableFields
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

export default ItemFormContent;
