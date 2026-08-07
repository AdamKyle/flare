import React, { ReactNode } from 'react';

import QueenCostSummary from './queen-cost-summary';
import QueenRerollFormProps from './types/queen-reroll-form-props';
import CraftingActionButton from '../../../shared/components/crafting-action-button';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import { useQueenRerollFlow } from '../hooks/use-queen-reroll-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

const QueenRerollForm = ({
  data,
  characterId,
  onDataReplaced,
  onChangeAction,
}: QueenRerollFormProps): ReactNode => {
  const {
    hasSlots,
    itemsApi,
    affixOptions,
    rerollTypeOptions,
    selectedSlotId,
    selectedSlot,
    selectedAffix,
    selectedRerollType,
    selectedCost,
    resultPreview,
    resultMessage,
    submitting,
    error,
    canSubmit,
    handleSelectSlot,
    handleSelectAffix,
    handleSelectRerollType,
    handleSubmit,
  } = useQueenRerollFlow({ characterId, data, onDataReplaced });

  const renderStatus = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderForm = (): ReactNode => {
    if (!hasSlots) {
      return (
        <Alert variant={AlertVariant.INFO}>
          You do not have any unique items to re-roll.
        </Alert>
      );
    }

    return (
      <div className="space-y-4">
        <label id="queen-reroll-item-label" className="block font-semibold">
          Unique item
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-item-label"
          items={itemsApi.items}
          selection_placeholder={
            itemsApi.loading ? 'Loading items…' : 'Select a unique item'
          }
          on_select={handleSelectSlot}
          searchable
          search_value={itemsApi.searchText}
          on_search={itemsApi.setSearchText}
          can_load_more={itemsApi.canLoadMore}
          is_loading_more={itemsApi.isLoadingMore}
          on_end_reached={itemsApi.onEndReached}
          empty_message="No unique items are available."
          disabled={itemsApi.loading}
        />

        <label id="queen-reroll-affix-label" className="block font-semibold">
          Affix
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-affix-label"
          items={affixOptions}
          selection_placeholder="Select Prefix, Suffix, or Both"
          disabled={!selectedSlotId}
          on_select={handleSelectAffix}
        />

        <label id="queen-reroll-category-label" className="block font-semibold">
          Reroll category
        </label>
        <Dropdown
          aria_labelled_by="queen-reroll-category-label"
          items={rerollTypeOptions}
          selection_placeholder="Select a reroll category"
          disabled={!selectedAffix}
          on_select={handleSelectRerollType}
        />
      </div>
    );
  };

  const renderPreview = (): ReactNode => {
    if (resultPreview) {
      return (
        <CraftingActionPreview title="Reroll preview" status="success">
          <p
            role="status"
            aria-live="polite"
            className="text-sm text-emerald-700 dark:text-emerald-400"
          >
            {resultMessage ?? 'The item was re-rolled.'}
          </p>
          <CraftingItemPreview item={resultPreview} />
        </CraftingActionPreview>
      );
    }

    if (!selectedSlot) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Reroll preview"
        description="The replacement is random. The affix category selected below will be rerolled to a new, randomly generated value."
      >
        <CraftingItemPreview item={selectedSlot.preview} />
        {selectedAffix && (
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Reroll target: {selectedAffix}
            {selectedRerollType ? ` (${selectedRerollType})` : ''}
          </p>
        )}
        {selectedCost && (
          <QueenCostSummary
            goldDust={selectedCost.gold_dust}
            shards={selectedCost.shards}
          />
        )}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="space-y-2">
      {hasSlots && (
        <CraftingActionButton
          label={submitting ? 'Re rolling…' : 'Re roll'}
          on_click={() => void handleSubmit()}
          disabled={!canSubmit}
        />
      )}
      <CraftingActionButton
        label="Change Action"
        on_click={onChangeAction}
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );

  return (
    <CraftingActionLayout
      title="Queen of Hearts: Reroll an Affix"
      status={renderStatus()}
      form={renderForm()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/random-enchants"
      help_label="Random enchant help"
    />
  );
};

export default QueenRerollForm;
